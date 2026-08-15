<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\RechnungPosition;
use App\Models\Customer;
use App\Services\ZugferdService;
use App\Mail\RechnungVersendetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RechnungController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['rechnungsnummer', 'datum', 'faelligkeitsdatum', 'bruttobetrag', 'status'];

        if ($request->boolean('reset')) {
            $request->session()->forget(['rechnungen.sort', 'rechnungen.direction']);
        }

        if ($request->filled('sort') && in_array($request->input('sort'), $sortable, true)) {
            $sort = $request->input('sort');
            $request->session()->put('rechnungen.sort', $sort);
        } else {
            $sort = $request->session()->get('rechnungen.sort', 'datum');
            if (!in_array($sort, $sortable, true)) {
                $sort = 'datum';
            }
        }

        $direction = strtolower((string) $request->input('direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $request->session()->get('rechnungen.direction', 'desc');
            if (!in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'desc';
            }
        } else {
            $request->session()->put('rechnungen.direction', $direction);
        }

        $query = Rechnung::query();

        // Standard: Stornobelege ausblenden
        if ($request->input('status') !== 'stornobelege') {
            $query->where('ist_storno', false);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('rechnungsnummer', 'like', "%{$search}%")
                  ->orWhere('kunde_name', 'like', "%{$search}%")
                  ->orWhere('kunde_firma', 'like', "%{$search}%")
                  ->orWhere('kunde_email', 'like', "%{$search}%")
                  ->orWhere('intern_ref', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'stornobelege') {
                $query->where('ist_storno', true);
            } elseif (in_array($status, Rechnung::STATUSES)) {
                $query->where('status', $status);
            }
        }

        $rechnungen = $query->orderBy($sort, $direction)->paginate(25)->withQueryString();

        $stats = [
            'total'        => Rechnung::where('ist_storno', false)->count(),
            'draft'        => Rechnung::where('status', Rechnung::STATUS_DRAFT)->where('ist_storno', false)->count(),
            'bestaetigt'   => Rechnung::where('status', Rechnung::STATUS_BESTAETIGT)->where('ist_storno', false)->count(),
            'offen'        => Rechnung::where('status', Rechnung::STATUS_VERSENDET)->where('ist_storno', false)->count(),
            'bezahlt'      => Rechnung::where('status', Rechnung::STATUS_BEZAHLT)->where('ist_storno', false)->count(),
            'ueberfaellig' => Rechnung::where('status', Rechnung::STATUS_UEBERFAELLIG)->where('ist_storno', false)->count(),
            'storniert'    => Rechnung::where('status', Rechnung::STATUS_STORNIERT)->where('ist_storno', false)->count(),
            'stornobelege' => Rechnung::where('ist_storno', true)->count(),
        ];

        return view('rechnungen.index', compact('rechnungen', 'stats', 'sort', 'direction'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $tenant = auth()->user()->tenant;

        return view('rechnungen.create', compact('customers', 'tenant'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kunde_name'         => 'nullable|string|max:255',
            'kunde_firma'        => 'nullable|string|max:255',
            'kunde_email'        => 'nullable|email|max:255',
            'kunde_strasse'      => 'nullable|string|max:255',
            'kunde_plz'          => 'nullable|string|max:20',
            'kunde_ort'          => 'nullable|string|max:255',
            'kunde_land'         => 'nullable|string|size:2',
            'kunde_steuernummer' => 'nullable|string|max:50',
            'datum'              => 'required|date',
            'faelligkeitsdatum'  => 'required|date',
            'leistungsdatum'     => 'nullable|date',
            'steuersatz'         => 'required|numeric|min:0|max:100',
            'notizen'            => 'nullable|string',
            'intern_ref'         => 'nullable|string|max:100',
            'positions'          => 'required|array|min:1',
            'positions.*.beschreibung' => 'required|string|max:255',
            'positions.*.menge'        => 'required|numeric|min:0.01',
            'positions.*.einheit'      => 'nullable|string|max:10',
            'positions.*.einzelpreis'  => 'required|numeric|min:0',
        ]);

        $rechnung = DB::transaction(function () use ($validated) {
            $rechnung = Rechnung::create([
                'kunde_name'        => $validated['kunde_name'] ?? null,
                'kunde_firma'       => $validated['kunde_firma'] ?? null,
                'kunde_email'       => $validated['kunde_email'] ?? null,
                'kunde_strasse'     => $validated['kunde_strasse'] ?? null,
                'kunde_plz'         => $validated['kunde_plz'] ?? null,
                'kunde_ort'         => $validated['kunde_ort'] ?? null,
                'kunde_land'        => $validated['kunde_land'] ?? 'DE',
                'kunde_steuernummer'=> $validated['kunde_steuernummer'] ?? null,
                'datum'             => $validated['datum'],
                'faelligkeitsdatum' => $validated['faelligkeitsdatum'],
                'leistungsdatum'    => $validated['leistungsdatum'] ?? null,
                'steuersatz'        => $validated['steuersatz'],
                'notizen'           => $validated['notizen'] ?? null,
                'intern_ref'        => $validated['intern_ref'] ?? null,
            ]);

            foreach ($validated['positions'] as $i => $pos) {
                $netto = round($pos['menge'] * $pos['einzelpreis'], 2);

                $rechnung->positions()->create([
                    'position'     => $i + 1,
                    'beschreibung' => $pos['beschreibung'],
                    'menge'        => $pos['menge'],
                    'einheit'      => $pos['einheit'] ?? 'Stk',
                    'einzelpreis'  => $pos['einzelpreis'],
                    'nettobetrag'  => $netto,
                    'steuersatz'   => $validated['steuersatz'],
                ]);
            }

            $rechnung->recalculate();

            return $rechnung;
        });

        return redirect()
            ->route('rechnungen.show', $rechnung)
            ->with('success', 'Rechnung ' . $rechnung->rechnungsnummer . ' erstellt.');
    }

    public function show(Rechnung $rechnung)
    {
        $rechnung->load('positions');

        $tenant = auth()->user()->tenant;

        return view('rechnungen.show', compact('rechnung', 'tenant'));
    }

    public function edit(Rechnung $rechnung)
    {
        abort_unless($rechnung->isEditable(), 422, 'Rechnung kann nicht mehr bearbeitet werden.');

        $rechnung->load('positions');
        $customers = Customer::orderBy('name')->get();
        $tenant = auth()->user()->tenant;

        return view('rechnungen.edit', compact('rechnung', 'customers', 'tenant'));
    }

    public function update(Request $request, Rechnung $rechnung)
    {
        abort_unless($rechnung->isEditable(), 422, 'Rechnung kann nicht mehr bearbeitet werden.');

        $validated = $request->validate([
            'kunde_name'         => 'nullable|string|max:255',
            'kunde_firma'        => 'nullable|string|max:255',
            'kunde_email'        => 'nullable|email|max:255',
            'kunde_strasse'      => 'nullable|string|max:255',
            'kunde_plz'          => 'nullable|string|max:20',
            'kunde_ort'          => 'nullable|string|max:255',
            'kunde_land'         => 'nullable|string|size:2',
            'kunde_steuernummer' => 'nullable|string|max:50',
            'datum'              => 'required|date',
            'faelligkeitsdatum'  => 'required|date',
            'leistungsdatum'     => 'nullable|date',
            'steuersatz'         => 'required|numeric|min:0|max:100',
            'notizen'            => 'nullable|string',
            'intern_ref'         => 'nullable|string|max:100',
            'positions'          => 'required|array|min:1',
            'positions.*.beschreibung' => 'required|string|max:255',
            'positions.*.menge'        => 'required|numeric|min:0.01',
            'positions.*.einheit'      => 'nullable|string|max:10',
            'positions.*.einzelpreis'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $rechnung) {
            $rechnung->update([
                'kunde_name'        => $validated['kunde_name'] ?? null,
                'kunde_firma'       => $validated['kunde_firma'] ?? null,
                'kunde_email'       => $validated['kunde_email'] ?? null,
                'kunde_strasse'     => $validated['kunde_strasse'] ?? null,
                'kunde_plz'         => $validated['kunde_plz'] ?? null,
                'kunde_ort'         => $validated['kunde_ort'] ?? null,
                'kunde_land'        => $validated['kunde_land'] ?? 'DE',
                'kunde_steuernummer'=> $validated['kunde_steuernummer'] ?? null,
                'datum'             => $validated['datum'],
                'faelligkeitsdatum' => $validated['faelligkeitsdatum'],
                'leistungsdatum'    => $validated['leistungsdatum'] ?? null,
                'steuersatz'        => $validated['steuersatz'],
                'notizen'           => $validated['notizen'] ?? null,
                'intern_ref'        => $validated['intern_ref'] ?? null,
            ]);

            $rechnung->positions()->delete();

            foreach ($validated['positions'] as $i => $pos) {
                $netto = round($pos['menge'] * $pos['einzelpreis'], 2);

                $rechnung->positions()->create([
                    'position'     => $i + 1,
                    'beschreibung' => $pos['beschreibung'],
                    'menge'        => $pos['menge'],
                    'einheit'      => $pos['einheit'] ?? 'Stk',
                    'einzelpreis'  => $pos['einzelpreis'],
                    'nettobetrag'  => $netto,
                    'steuersatz'   => $validated['steuersatz'],
                ]);
            }

            $rechnung->recalculate();
        });

        return redirect()
            ->route('rechnungen.show', $rechnung)
            ->with('success', 'Rechnung aktualisiert.');
    }

    public function destroy(Rechnung $rechnung)
    {
        abort(403, 'Rechnungen können nicht gelöscht werden.');
    }

    // ── PDF Export ───────────────────────────────────────────

    public function pdf(Rechnung $rechnung)
    {
        $tenant = auth()->user()->tenant;
        $service = new ZugferdService($tenant);

        $relativePath = $service->generatePdf($rechnung);

        if (!$rechnung->pdf_path) {
            $rechnung->update(['pdf_path' => $relativePath]);
        }

        $absolutePath = storage_path("app/{$relativePath}");

        return response()->download($absolutePath, $rechnung->rechnungsnummer . '.pdf');
    }

    public function pdfView(Rechnung $rechnung)
    {
        $tenant = auth()->user()->tenant;
        $service = new ZugferdService($tenant);

        $relativePath = $rechnung->pdf_path;

        if (!$relativePath || !file_exists(storage_path("app/{$relativePath}"))) {
            $relativePath = $service->generatePdf($rechnung);
            $rechnung->update(['pdf_path' => $relativePath]);
        }

        $absolutePath = storage_path("app/{$relativePath}");

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ── Storno PDF ──────────────────────────────────────────

    public function stornoPdf(Rechnung $rechnung)
    {
        $storno = $rechnung->stornoBeleg()->first();

        if (!$storno) {
            return back()->with('error', 'Kein Stornobeleg vorhanden.');
        }

        $tenant = auth()->user()->tenant;
        $service = new ZugferdService($tenant);

        $relativePath = $storno->storno_pdf_path;

        if (!$relativePath || !file_exists(storage_path("app/{$relativePath}"))) {
            $relativePath = $service->generateStornoPdf($storno);
            $storno->update(['storno_pdf_path' => $relativePath]);
        }

        $absolutePath = storage_path("app/{$relativePath}");

        return response()->download($absolutePath, 'Stornobeleg-' . $storno->rechnungsnummer . '.pdf');
    }

    public function stornoPdfView(Rechnung $rechnung)
    {
        $storno = $rechnung->stornoBeleg()->first();

        if (!$storno) {
            return back()->with('error', 'Kein Stornobeleg vorhanden.');
        }

        $tenant = auth()->user()->tenant;
        $service = new ZugferdService($tenant);

        $relativePath = $storno->storno_pdf_path;

        if (!$relativePath || !file_exists(storage_path("app/{$relativePath}"))) {
            $relativePath = $service->generateStornoPdf($storno);
            $storno->update(['storno_pdf_path' => $relativePath]);
        }

        $absolutePath = storage_path("app/{$relativePath}");

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ── Duplizieren ─────────────────────────────────────────

    public function duplicate(Rechnung $rechnung)
    {
        $newRechnung = $rechnung->replicate();
        $newRechnung->status = Rechnung::STATUS_DRAFT;
        $newRechnung->datum = now()->toDateString();
        $newRechnung->faelligkeitsdatum = now()->addDays(30)->toDateString();
        $newRechnung->rechnungsnummer = null; // auto-generate
        $newRechnung->nettobetrag = 0;
        $newRechnung->steuerbetrag = 0;
        $newRechnung->bruttobetrag = 0;
        $newRechnung->save();

        foreach ($rechnung->positions as $pos) {
            $newPos = $pos->replicate();
            $newPos->rechnung_id = $newRechnung->id;
            $newPos->save();
        }

        $newRechnung->recalculate();

        return redirect()
            ->route('rechnungen.show', $newRechnung)
            ->with('success', 'Rechnung dupliziert als ' . $newRechnung->rechnungsnummer . '.');
    }

    // ── Status ändern ───────────────────────────────────────

    public function status(Request $request, Rechnung $rechnung)
    {
        $request->validate([
            'status' => 'required|in:bestaetigt,bezahlt,storniert',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'bezahlt') {
            $data['bezahldatum'] = now()->toDateString();
        }

        $rechnung->update($data);

        // Stornobeleg erstellen
        if ($request->status === 'storniert') {
            $storno = $rechnung->createStornoBeleg();

            // PDF generieren
            $tenant = auth()->user()->tenant;
            $service = new ZugferdService($tenant);
            $stornoPdfPath = $service->generateStornoPdf($storno);
            $storno->update(['storno_pdf_path' => $stornoPdfPath]);

            return back()->with('success', 'Rechnung storniert. Stornobeleg: ' . $storno->rechnungsnummer);
        }

        return back()->with('success', 'Status aktualisiert.');
    }

    // ── E-Mail versenden ────────────────────────────────────

    public function sendEmail(Request $request, Rechnung $rechnung)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $tenant = auth()->user()->tenant;

        Mail::to($request->email)
            ->send(new RechnungVersendetMail($rechnung, $tenant));

        if ($rechnung->isBestaetigt()) {
            $rechnung->update(['status' => Rechnung::STATUS_VERSENDET]);
        }

        return back()->with('success', "Rechnung an {$request->email} versendet.");
    }

    // ── Mahnung versenden ───────────────────────────────────

    public function sendMahnung(Request $request, Rechnung $rechnung)
    {
        $request->validate([
            'email'       => 'required|email',
            'mahnung_text' => 'nullable|string',
        ]);

        $tenant = auth()->user()->tenant;

        Mail::to($request->email)
            ->send(new \App\Mail\MahnungMail($rechnung, $tenant, $request->mahnung_text));

        $rechnung->sendMahnung($request->mahnung_text);

        return back()->with('success', "Mahnung an {$request->email} versendet.");
    }

    // ── Kunde auswählen (AJAX) ──────────────────────────────

    public function getCustomer(Customer $customer)
    {
        return response()->json([
            'name'     => $customer->name,
            'firma'    => $customer->company,
            'email'    => $customer->email,
            'strasse'  => $customer->street,
            'plz'      => $customer->zip,
            'ort'      => $customer->city,
            'land'     => $customer->country,
        ]);
    }
}
