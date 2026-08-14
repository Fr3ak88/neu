<?php

namespace App\Http\Controllers;

use App\Models\RechnungAuftrag;
use App\Models\RechnungAuftragPosition;
use App\Models\Customer;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RechnungAuftragController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['auftragsnummer', 'kunde', 'typ', 'intervall', 'startdatum', 'naechste_erstellung'];

        if ($request->boolean('reset')) {
            $request->session()->forget(['auftraege.sort', 'auftraege.direction']);
        }

        if ($request->filled('sort') && in_array($request->input('sort'), $sortable, true)) {
            $sort = $request->input('sort');
            $request->session()->put('auftraege.sort', $sort);
        } else {
            $sort = $request->session()->get('auftraege.sort', 'auftragsnummer');
            if (!in_array($sort, $sortable, true)) {
                $sort = 'auftragsnummer';
            }
        }

        $direction = strtolower((string) $request->input('direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $request->session()->get('auftraege.direction', 'desc');
            if (!in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'desc';
            }
        } else {
            $request->session()->put('auftraege.direction', $direction);
        }

        $query = RechnungAuftrag::query()->with('positions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('auftragsnummer', 'like', "%{$search}%")
                  ->orWhere('bezeichnung', 'like', "%{$search}%")
                  ->orWhere('kunde_name', 'like', "%{$search}%")
                  ->orWhere('kunde_firma', 'like', "%{$search}%")
                  ->orWhere('kunde_email', 'like', "%{$search}%");
            });
        }

        if ($typ = $request->input('typ')) {
            if (in_array($typ, array_keys(RechnungAuftrag::TYPEN), true)) {
                $query->where('typ', $typ);
            }
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['erstellt', 'aktiv', 'pausiert', 'faellig', 'abgelaufen'], true)) {
                $query->where(function ($q) use ($status) {
                    match ($status) {
                        'erstellt'   => $q->where('typ', RechnungAuftrag::TYP_EINMALIG),
                        'aktiv'      => $q->where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                          ->where('aktiv', true)
                                          ->where(fn($qq) => $qq->whereNull('enddatum')->orWhere('enddatum', '>=', now()->toDateString())),
                        'pausiert'   => $q->where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                          ->where('aktiv', false),
                        'faellig'    => $q->where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                          ->where('aktiv', true)
                                          ->where('naechste_erstellung', '<=', now()->toDateString())
                                          ->where(fn($qq) => $qq->whereNull('enddatum')->orWhere('enddatum', '>=', now()->toDateString())),
                        'abgelaufen' => $q->where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                          ->whereNotNull('enddatum')->where('enddatum', '<', now()->toDateString()),
                    };
                });
            }
        }

        if ($sort === 'kunde') {
            $query->orderByRaw("COALESCE(NULLIF(kunde_firma, ''), NULLIF(kunde_name, ''), '') {$direction}");
        } else {
            $query->orderBy($sort, $direction);
        }

        $auftraege = $query->paginate(25)->withQueryString();

        $stats = [
            'total'        => RechnungAuftrag::count(),
            'erstellt'     => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_EINMALIG)->count(),
            'aktiv'        => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                ->where('aktiv', true)
                                ->where(fn($q) => $q->whereNull('enddatum')->orWhere('enddatum', '>=', now()->toDateString()))
                                ->count(),
            'pausiert'     => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                ->where('aktiv', false)->count(),
            'faellig'      => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                ->where('aktiv', true)
                                ->where('naechste_erstellung', '<=', now()->toDateString())
                                ->where(fn($q) => $q->whereNull('enddatum')->orWhere('enddatum', '>=', now()->toDateString()))
                                ->count(),
            'abgelaufen'   => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)
                                ->whereNotNull('enddatum')->where('enddatum', '<', now()->toDateString())->count(),
            'einmalig'     => RechnungAuftrag::where('typ', RechnungAuftrag::TYP_EINMALIG)->count(),
            'wiederkehrend'=> RechnungAuftrag::where('typ', RechnungAuftrag::TYP_WIEDERKEHREND)->count(),
        ];

        return view('rechnungen.auftraege.index', compact('auftraege', 'stats', 'sort', 'direction'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('rechnungen.auftraege.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'typ'                => 'required|in:einmalig,wiederkehrend',
            'customer_id'        => 'nullable|exists:customers,id',
            'kunde_name'         => 'nullable|string|max:255',
            'kunde_firma'        => 'nullable|string|max:255',
            'kunde_email'        => 'nullable|email|max:255',
            'kunde_strasse'      => 'nullable|string|max:255',
            'kunde_plz'          => 'nullable|string|max:20',
            'kunde_ort'          => 'nullable|string|max:255',
            'kunde_land'         => 'nullable|string|size:2',
            'kunde_steuernummer' => 'nullable|string|max:50',
            'intervall'          => 'required_if:typ,wiederkehrend|nullable|in:woechentlich,monatlich,vierteljaehrlich,jaehrlich',
            'faelligkeit_tage'   => 'required|integer|min:0|max:365',
            'notizen'            => 'nullable|string',
            'startdatum'         => 'required|date',
            'enddatum'           => 'nullable|date|after_or_equal:startdatum',
            'positions'          => 'required|array|min:1',
            'positions.*.beschreibung' => 'required|string|max:255',
            'positions.*.menge'        => 'required|numeric|min:0.01',
            'positions.*.einheit'      => 'nullable|string|max:10',
            'positions.*.einzelpreis'  => 'required|numeric|min:0',
            'positions.*.steuersatz'   => 'required|numeric|min:0|max:100',
            'positions.*.rabatt'       => 'nullable|numeric|min:0|max:100',
            'positions.*.notizen'      => 'nullable|string|max:1000',
        ]);

        $auftrag = DB::transaction(function () use ($validated) {
            $kundeName = $validated['kunde_firma'] ?? $validated['kunde_name'] ?? 'Unbekannt';
            $bezeichnung = $kundeName . ' – ' . now()->format('d.m.Y H:i');

            $auftrag = RechnungAuftrag::create([
                'bezeichnung'       => $bezeichnung,
                'typ'               => $validated['typ'],
                'customer_id'       => $validated['customer_id'] ?? null,
                'kunde_name'        => $validated['kunde_name'] ?? null,
                'kunde_firma'       => $validated['kunde_firma'] ?? null,
                'kunde_email'       => $validated['kunde_email'] ?? null,
                'kunde_strasse'     => $validated['kunde_strasse'] ?? null,
                'kunde_plz'         => $validated['kunde_plz'] ?? null,
                'kunde_ort'         => $validated['kunde_ort'] ?? null,
                'kunde_land'        => $validated['kunde_land'] ?? 'DE',
                'kunde_steuernummer'=> $validated['kunde_steuernummer'] ?? null,
                'intervall'         => $validated['intervall'] ?? null,
                'faelligkeit_tage'  => $validated['faelligkeit_tage'],
                'notizen'           => $validated['notizen'] ?? null,
                'startdatum'        => $validated['startdatum'],
                'enddatum'          => $validated['enddatum'] ?? null,
                'naechste_erstellung' => $validated['startdatum'],
            ]);

            foreach ($validated['positions'] as $i => $pos) {
                $auftrag->positions()->create([
                    'position'     => $i + 1,
                    'beschreibung' => $pos['beschreibung'],
                    'menge'        => $pos['menge'],
                    'einheit'      => $pos['einheit'] ?? 'Stk',
                    'einzelpreis'  => $pos['einzelpreis'],
                    'steuersatz'   => $pos['steuersatz'],
                    'rabatt'       => $pos['rabatt'] ?? 0,
                    'notizen'      => $pos['notizen'] ?? null,
                ]);
            }

            return $auftrag;
        });

        return redirect()
            ->route('auftraege.show', $auftrag)
            ->with('success', 'Auftrag "' . $auftrag->auftragsnummer . '" erstellt.');
    }

    public function show(RechnungAuftrag $auftrag)
    {
        $auftrag->load(['positions', 'rechnungen', 'customer']);

        return view('rechnungen.auftraege.show', compact('auftrag'));
    }

    public function pdf(RechnungAuftrag $auftrag)
    {
        $auftrag->load('positions');
        $tenant = Tenant::first();

        $html = view('rechnungen.auftraege.pdf', [
            'auftrag'  => $auftrag,
            'tenant'   => $tenant,
            'positions' => $auftrag->positions,
        ])->render();

        $pdf = Pdf::loadHtml($html)
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'sans-serif')
            ->output();

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $auftrag->auftragsnummer . '.pdf"',
        ]);
    }

    public function sendEmail(Request $request, RechnungAuftrag $auftrag)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $tenant = Tenant::first();

        Mail::to($request->email)
            ->send(new \App\Mail\AuftragVersendetMail($auftrag, $tenant));

        return back()->with('success', "Auftrag an {$request->email} versendet.");
    }

    public function edit(RechnungAuftrag $auftrag)
    {
        $auftrag->load('positions');
        $customers = Customer::orderBy('name')->get();

        return view('rechnungen.auftraege.edit', compact('auftrag', 'customers'));
    }

    public function update(Request $request, RechnungAuftrag $auftrag)
    {
        $validated = $request->validate([
            'typ'                => 'required|in:einmalig,wiederkehrend',
            'customer_id'        => 'nullable|exists:customers,id',
            'kunde_name'         => 'nullable|string|max:255',
            'kunde_firma'        => 'nullable|string|max:255',
            'kunde_email'        => 'nullable|email|max:255',
            'kunde_strasse'      => 'nullable|string|max:255',
            'kunde_plz'          => 'nullable|string|max:20',
            'kunde_ort'          => 'nullable|string|max:255',
            'kunde_land'         => 'nullable|string|size:2',
            'kunde_steuernummer' => 'nullable|string|max:50',
            'intervall'          => 'required_if:typ,wiederkehrend|nullable|in:woechentlich,monatlich,vierteljaehrlich,jaehrlich',
            'faelligkeit_tage'   => 'required|integer|min:0|max:365',
            'notizen'            => 'nullable|string',
            'startdatum'         => 'required|date',
            'enddatum'           => 'nullable|date|after_or_equal:startdatum',
            'aktiv'              => 'boolean',
            'positions'          => 'required|array|min:1',
            'positions.*.beschreibung' => 'required|string|max:255',
            'positions.*.menge'        => 'required|numeric|min:0.01',
            'positions.*.einheit'      => 'nullable|string|max:10',
            'positions.*.einzelpreis'  => 'required|numeric|min:0',
            'positions.*.steuersatz'   => 'required|numeric|min:0|max:100',
            'positions.*.rabatt'       => 'nullable|numeric|min:0|max:100',
            'positions.*.notizen'      => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $auftrag) {
            $auftrag->update([
                'typ'               => $validated['typ'],
                'customer_id'       => $validated['customer_id'] ?? null,
                'kunde_name'        => $validated['kunde_name'] ?? null,
                'kunde_firma'       => $validated['kunde_firma'] ?? null,
                'kunde_email'       => $validated['kunde_email'] ?? null,
                'kunde_strasse'     => $validated['kunde_strasse'] ?? null,
                'kunde_plz'         => $validated['kunde_plz'] ?? null,
                'kunde_ort'         => $validated['kunde_ort'] ?? null,
                'kunde_land'        => $validated['kunde_land'] ?? 'DE',
                'kunde_steuernummer'=> $validated['kunde_steuernummer'] ?? null,
                'intervall'         => $validated['intervall'] ?? null,
                'faelligkeit_tage'  => $validated['faelligkeit_tage'],
                'notizen'           => $validated['notizen'] ?? null,
                'startdatum'        => $validated['startdatum'],
                'enddatum'          => $validated['enddatum'] ?? null,
                'aktiv'             => $validated['aktiv'] ?? $auftrag->aktiv,
            ]);

            $auftrag->positions()->delete();

            foreach ($validated['positions'] as $i => $pos) {
                $auftrag->positions()->create([
                    'position'     => $i + 1,
                    'beschreibung' => $pos['beschreibung'],
                    'menge'        => $pos['menge'],
                    'einheit'      => $pos['einheit'] ?? 'Stk',
                    'einzelpreis'  => $pos['einzelpreis'],
                    'steuersatz'   => $pos['steuersatz'],
                    'rabatt'       => $pos['rabatt'] ?? 0,
                    'notizen'      => $pos['notizen'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('auftraege.show', $auftrag)
            ->with('success', 'Auftrag aktualisiert.');
    }

    public function destroy(RechnungAuftrag $auftrag)
    {
        $auftrag->delete();

        return redirect()
            ->route('auftraege.index')
            ->with('success', 'Auftrag gelöscht.');
    }

    // ── Sofort erstellen ─────────────────────────────────────

    public function erstelleJetzt(RechnungAuftrag $auftrag)
    {
        $rechnung = $auftrag->erstelleRechnung();

        return redirect()
            ->route('rechnungen.show', $rechnung)
            ->with('success', 'Rechnung ' . $rechnung->rechnungsnummer . ' aus Auftrag erstellt.');
    }

    // ── Aktiv/Inaktiv schalten ──────────────────────────────

    public function toggle(RechnungAuftrag $auftrag)
    {
        $auftrag->update(['aktiv' => !$auftrag->aktiv]);

        $status = $auftrag->aktiv ? 'aktiviert' : 'deaktiviert';

        return back()->with('success', "Auftrag {$status}.");
    }
}
