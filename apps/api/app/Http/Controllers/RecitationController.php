<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers;

use App\Models\Recitation;
use App\Models\WhisperJob;
use App\Models\OrgSetting;
use App\Jobs\WhisperTranscribeJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecitationController extends Controller
{
    /**
     * uploadUrl returns a pre-signed S3 PUT URL for client upload.
     * The client uploads browser->S3 directly; we never proxy audio through PHP.
     */
    public function uploadUrl(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string',
            'mime' => 'required|string',
        ]);

        $key = 'recitations/'. $request->user()->id . '/' . now()->timestamp . '-' . preg_replace('/[^a-zA-Z0-9._-]/','_', $data['filename']);
        $disk = Storage::disk('s3');

        // Generate a temporary signed PUT URL (5 minutes)
        $client = $disk->getClient();
        $cmd = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $key,
            'ContentType' => $data['mime'],
        ]);
        $req = $client->createPresignedRequest($cmd, '+5 minutes');

        return response()->json([
            'upload_url' => (string)$req->getUri(),
            's3_key' => $key,
        ]);
    }

    /**
     * submit creates a Recitation + WhisperJob and dispatches a queue job.
     * expected_tokens should be normalized Arabic text for the target ayahs.
     * Includes feature flag guards for Tajweed analysis toggle system.
     */
    public function submit(Request $request)
    {
        // Check feature flags before processing
        $org = OrgSetting::first();
        $orgDefault = $org?->tajweed_default ?? true;
        $userPref = (bool)($request->user()->settings['tajweed_enabled'] ?? true);

        $data = $request->validate([
            's3_key' => 'required|string',
            'mime' => 'nullable|string',
            'surah' => 'nullable|integer|min:1|max:114',
            'from_ayah' => 'nullable|integer|min:1',
            'to_ayah' => 'nullable|integer|min:1',
            'expected_tokens' => 'nullable|array',
            'assignment_auto_ai' => 'nullable|boolean',
        ]);

        $assignmentAI = array_key_exists('assignment_auto_ai', $data) ? (bool)$data['assignment_auto_ai'] : true;

        // Guard: if any flag is disabled, return early
        if (!($orgDefault && $userPref && $assignmentAI)) {
            return response()->json([
                'disabled' => true,
                'reason' => 'Tajweed analysis is disabled by current settings.',
            ], 200);
        }

        $rec = Recitation::create([
            'user_id' => $request->user()->id,
            'surah' => $data['surah'] ?? null,
            'from_ayah' => $data['from_ayah'] ?? null,
            'to_ayah' => $data['to_ayah'] ?? null,
            's3_key' => $data['s3_key'],
            'mime' => $data['mime'] ?? null,
            'expected_tokens' => $data['expected_tokens'] ?? null,
        ]);

        $job = WhisperJob::create([
            'recitation_id' => $rec->id,
            'status' => 'queued',
        ]);

        // Dispatch to DB queue (works on cPanel cron)
        dispatch(new WhisperTranscribeJob($job->id));

        return response()->json(['recitation_id'=>$rec->id, 'job_id'=>$job->id]);
    }

    /**
     * status returns current WhisperJob state; UI polls until 'done' or 'failed'.
     */
    public function status(Request $request, int $id)
    {
        $job = WhisperJob::with('recitation')->findOrFail($id);
        $this->authorize('view', $job->recitation); // ensure same user/teacher/admin policy

        return response()->json([
            'status' => $job->status,
            'result' => $job->result_json,
            'error' => $job->error,
        ]);
    }
}