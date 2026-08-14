<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;

class EmailSettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'MAIL_MAILER'       => config('mail.mailers.smtp.transport') ? config('mail.default') : env('MAIL_MAILER', 'log'),
            'MAIL_HOST'         => env('MAIL_HOST', '127.0.0.1'),
            'MAIL_PORT'         => env('MAIL_PORT', '2525'),
            'MAIL_USERNAME'     => env('MAIL_USERNAME', ''),
            'MAIL_PASSWORD'     => env('MAIL_PASSWORD', ''),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS', ''),
            'MAIL_FROM_NAME'    => env('MAIL_FROM_NAME', ''),
        ];

        return view('settings.email.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'MAIL_HOST'         => 'required|string',
            'MAIL_PORT'         => 'required|integer',
            'MAIL_USERNAME'     => 'nullable|string',
            'MAIL_PASSWORD'     => 'nullable|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME'    => 'required|string',
        ]);

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return back()->with('error', '.env-Datei nicht gefunden.');
        }

        $envContent = file_get_contents($envPath);

        $updates = [
            'MAIL_MAILER'       => 'smtp',
            'MAIL_HOST'         => $request->MAIL_HOST,
            'MAIL_PORT'         => (string) $request->MAIL_PORT,
            'MAIL_USERNAME'     => $request->MAIL_USERNAME ?? '',
            'MAIL_PASSWORD'     => $request->MAIL_PASSWORD ?? '',
            'MAIL_FROM_ADDRESS' => $request->MAIL_FROM_ADDRESS,
            'MAIL_FROM_NAME'    => '"' . str_replace('"', '\"', $request->MAIL_FROM_NAME) . '"',
        ];

        foreach ($updates as $key => $value) {
            $envContent = $this->setEnvValue($envContent, $key, $value);
        }

        file_put_contents($envPath, $envContent);

        Artisan::call('config:clear');

        return back()->with('success', 'E-Mail-Einstellungen gespeichert. Config-Cache wurde geleert.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'MAIL_HOST'         => 'required|string',
            'MAIL_PORT'         => 'required|integer',
            'MAIL_USERNAME'     => 'nullable|string',
            'MAIL_PASSWORD'     => 'nullable|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME'    => 'required|string',
            'TEST_EMAIL'        => 'required|email',
        ]);

        config([
            'mail.default'                => 'smtp',
            'mail.mailers.smtp.host'      => $request->MAIL_HOST,
            'mail.mailers.smtp.port'      => $request->MAIL_PORT,
            'mail.mailers.smtp.username'  => $request->MAIL_USERNAME,
            'mail.mailers.smtp.password'  => $request->MAIL_PASSWORD ?: config('mail.mailers.smtp.password', ''),
            'mail.from.address'           => $request->MAIL_FROM_ADDRESS,
            'mail.from.name'              => $request->MAIL_FROM_NAME,
        ]);

        try {
            $to = $request->TEST_EMAIL;
            Mail::raw('Dies ist eine Test-E-Mail von Fritzler-Solution.', function ($message) use ($to, $request) {
                $message->to($to)
                    ->subject('Test-E-Mail von Fritzler-Solution')
                    ->from($request->MAIL_FROM_ADDRESS, $request->MAIL_FROM_NAME);
            });

            return response()->json([
                'success' => true,
                'message' => 'Test-E-Mail erfolgreich gesendet an ' . $to,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'E-Mail-Versand fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    private function setEnvValue(string $content, string $key, string $value): string
    {
        $escapedValue = preg_replace('/\s+/', '\\ ', $value);

        if (preg_match('/^' . preg_quote($key) . '=/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key) . '=.*/m', $key . '=' . $escapedValue, $content);
        } else {
            $content .= "\n" . $key . '=' . $escapedValue;
        }

        return $content;
    }
}
