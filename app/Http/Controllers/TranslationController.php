<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class TranslationController extends Controller
{
    /**
     * Handle the voice translation request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function translate(Request $request)
    {
        // 1. Validate the request.
        $request->validate(['audio' => 'required|file|mimes:wav,mp3,webm,m4a']);
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key is not configured.'], 500);
        }

        try {
            // --- STEP 1: TRANSCRIBE BANGLA AUDIO TO BANGLA TEXT ---
            // We use the 'transcriptions' endpoint and let Whisper auto-detect the language.
            $transcriptionResponse = Http::withToken($apiKey)
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    ['name' => 'file', 'contents' => file_get_contents($request->file('audio')), 'filename' => 'audio.webm'],
                    ['name' => 'model', 'contents' => 'whisper-1'],
                ]);

            if ($transcriptionResponse->failed()) {
                return response()->json(['error' => 'Failed to transcribe audio.', 'details' => $transcriptionResponse->json()], 500);
            }

            $banglaText = $transcriptionResponse->json()['text'];

            if (empty($banglaText)) {
                return response()->json(['translation' => 'Could not understand the audio. Please try again.']);
            }

            // --- STEP 2: TRANSLATE BANGLA TEXT TO ENGLISH TEXT USING GPT ---
            // This provides a much higher quality translation.
            $translationResponse = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o', // Using a powerful model for translation
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert linguistics translator. Your task is to translate the following Bengali text into fluent, natural-sounding English. Preserve the original meaning and nuance as accurately as possible.'],
                        ['role' => 'user', 'content' => $banglaText],
                    ],
                    'temperature' => 0.2,
                ]);

            if ($translationResponse->failed()) {
                return response()->json(['error' => 'Failed to translate text.', 'details' => $translationResponse->json()], 500);
            }

            $englishText = $translationResponse->json()['choices'][0]['message']['content'];

            // --- STEP 3: CONVERT ENGLISH TEXT TO SPEECH ---
            $ttsResponse = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'tts-1',
                    'input' => $englishText,
                    'voice' => 'alloy',
                ]);

            if ($ttsResponse->failed()) {
                return response()->json([
                    'original' => $banglaText,
                    'translation' => $englishText,
                    'error' => 'Could not generate audio, but here is the text translation.'
                ]);
            }

            $audioContent = base64_encode($ttsResponse->body());

            // --- FINAL RESPONSE ---
            return response()->json([
                'original' => $banglaText,
                'translation' => $englishText,
                'audio_base64' => $audioContent,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }
}

