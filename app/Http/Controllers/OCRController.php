<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OCRController extends Controller
{
    public function processImage(Request $request)
    {
        $apiKey = env('GOOGLE_VISION_API_KEY');

        $image = file_get_contents($request->file('image')->getRealPath());
        $encodedImage = base64_encode($image);

        $url = "https://vision.googleapis.com/v1/images:annotate?key=$apiKey";
        $body = [
            'requests' => [
                [
                    'image' => ['content' => $encodedImage],
                    'features' => [['type' => 'TEXT_DETECTION']],
                ],
            ],
        ];

        try {
            $response = Http::post($url, $body);
            $responseData = $response->json();

            $detectedText = $responseData['responses'][0]['textAnnotations'][0]['description'] ?? '';

            Log::info('Google Vision Detected Text: ' . $detectedText);

            $gptResponse = $this->analyzeTextWithOpenAI($detectedText);

            return response()->json(['text' => $detectedText, 'gptResponse' => $gptResponse]);

        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            return response()->json(['error' => 'Error processing image.'], 500);
        }
    }

    public function processText(Request $request)
    {
        $inputText = $request->input('text');

        if (empty($inputText)) {
            return response()->json(['error' => 'No text provided.'], 400);
        }

        try {
            Log::info('Manually Inputted Text: ' . $inputText);
            $gptResponse = $this->analyzeTextWithOpenAI($inputText);
            return response()->json(['gptResponse' => $gptResponse]);

        } catch (\Exception $e) {
            Log::error('Error processing text: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the text.'], 500);
        }
    }

    private function analyzeTextWithOpenAI($text)
{
    $apiKey = env('OPENAI_API_KEY');  // Ensure the API key is set

    // New prompt to request three example sentences directly
    $prompt = "You are a Japanese language teacher. Provide three example sentences that answer the following homework question. Each example should be grammatically correct, suitable for a beginner or intermediate learner, and include an English translation.\n\nHomework Question:\n" . $text . "\n\nExample sentences:";

    $messages = [
        [
            'role' => 'user',
            'content' => $prompt,
        ]
    ];

    try {
        // Send a single request to OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(60)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 600,
            'temperature' => 0.7, // Adjust temperature as needed
        ]);

        $responseArray = $response->json();

        Log::info('OpenAI GPT Response: ' . json_encode($responseArray));

        if (isset($responseArray['error'])) {
            throw new \Exception($responseArray['error']['message']);
        }

        // Get the generated sentences
        $answer = $responseArray['choices'][0]['message']['content'] ?? '';

        return $answer;

    } catch (\Exception $e) {
        Log::error('Error with OpenAI API request: ' . $e->getMessage());
        return 'An error occurred while communicating with the OpenAI API.';
    }
}

    
    

}
