<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.storage', [
            'driver'      => Setting::get('storage_driver', 'local'),
            // S3
            's3Key'       => Setting::get('storage_s3_key', ''),
            's3SecretSet' => (bool) Setting::get('storage_s3_secret'),
            's3Region'    => Setting::get('storage_s3_region', 'us-east-1'),
            's3Bucket'    => Setting::get('storage_s3_bucket', ''),
            's3Endpoint'  => Setting::get('storage_s3_endpoint', ''),
            's3Url'       => Setting::get('storage_s3_url', ''),
            's3PathStyle' => Setting::get('storage_s3_path_style', '0') === '1',
            // SFTP
            'sftpHost'        => Setting::get('storage_sftp_host', ''),
            'sftpPort'        => Setting::get('storage_sftp_port', '22'),
            'sftpUsername'    => Setting::get('storage_sftp_username', ''),
            'sftpPasswordSet' => (bool) Setting::get('storage_sftp_password'),
            'sftpRoot'        => Setting::get('storage_sftp_root', '/'),
            'sftpUrl'         => Setting::get('storage_sftp_url', ''),
        ]);
    }

    public function update(Request $request)
    {
        $driver = $request->input('storage_driver', 'local');

        $rules = ['storage_driver' => ['required', 'in:local,s3,sftp']];

        if ($driver === 's3') {
            $rules['storage_s3_key']    = ['required', 'string', 'max:255'];
            $rules['storage_s3_region'] = ['required', 'string', 'max:100'];
            $rules['storage_s3_bucket'] = ['required', 'string', 'max:255'];
        }

        if ($driver === 'sftp') {
            $rules['storage_sftp_host']     = ['required', 'string', 'max:255'];
            $rules['storage_sftp_port']     = ['required', 'integer', 'min:1', 'max:65535'];
            $rules['storage_sftp_username'] = ['required', 'string', 'max:255'];
            $rules['storage_sftp_root']     = ['required', 'string', 'max:500'];
        }

        $request->validate($rules);

        Setting::set('storage_driver', $driver);

        if ($driver === 's3') {
            Setting::set('storage_s3_key',       $request->input('storage_s3_key'));
            Setting::set('storage_s3_region',     $request->input('storage_s3_region', 'us-east-1'));
            Setting::set('storage_s3_bucket',     $request->input('storage_s3_bucket'));
            Setting::set('storage_s3_endpoint',   $request->input('storage_s3_endpoint', ''));
            Setting::set('storage_s3_url',        $request->input('storage_s3_url', ''));
            Setting::set('storage_s3_path_style', $request->boolean('storage_s3_path_style') ? '1' : '0');

            // Only overwrite the secret if a new value was actually typed
            $secret = $request->input('storage_s3_secret', '');
            if ($secret !== '') {
                Setting::set('storage_s3_secret', encrypt($secret));
            }
        }

        if ($driver === 'sftp') {
            Setting::set('storage_sftp_host',     $request->input('storage_sftp_host'));
            Setting::set('storage_sftp_port',     $request->input('storage_sftp_port', '22'));
            Setting::set('storage_sftp_username', $request->input('storage_sftp_username'));
            Setting::set('storage_sftp_root',     $request->input('storage_sftp_root', '/'));
            Setting::set('storage_sftp_url',      $request->input('storage_sftp_url', ''));

            $password = $request->input('storage_sftp_password', '');
            if ($password !== '') {
                Setting::set('storage_sftp_password', encrypt($password));
            }
        }

        return back()->with('success', 'Storage settings saved.');
    }

    public function test()
    {
        try {
            $disk     = DocumentStorageService::disk();
            $testPath = '.storage-test-' . time() . '.txt';

            Storage::disk($disk)->put($testPath, 'FM storage connectivity test');
            $exists = Storage::disk($disk)->exists($testPath);
            Storage::disk($disk)->delete($testPath);

            if ($exists) {
                return back()->with('success', 'Connection successful — storage is working correctly.');
            }

            return back()->with('error', 'Connection failed — file was not found after writing. Check your credentials and permissions.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }
}
