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
    
        // Step 1: Generate the initial answers
        $prompt1 = "You are a Japanese language teacher. Provide five example sentences that are grammatically correct in Japanese, based on the following homework question. Each example should be simple, appropriate for a beginner or intermediate learner, and must follow proper Japanese grammar rules. Also, provide an English translation and a brief explanation of the grammar being used in each sentence.\n\nHomework Question:\n" . $text . "\n\nExample sentences:";
    
        $messages1 = [
            [
                'role' => 'user',
                'content' => $prompt1,
            ]
        ];
    
        try {
            // Send the first request to OpenAI API
            $response1 = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => $messages1,
                'max_tokens' => 600,
                'temperature' => 0.7,
            ]);
    
            $responseArray1 = $response1->json();
    
            Log::info('OpenAI GPT Initial Response: ' . json_encode($responseArray1));
    
            if (isset($responseArray1['error'])) {
                throw new \Exception($responseArray1['error']['message']);
            }
    
            $initialAnswer = $responseArray1['choices'][0]['message']['content'] ?? '';
    
            // Step 2: Evaluate and refine the initial answer
            $prompt2 = "Please check the following Japanese sentences for grammatical correctness and natural language use. If all the sentences are correct and natural, respond with 'All sentences are correct and natural.' If there are any mistakes or unnatural expressions, provide the corrected version of each sentence:\n\n" . $initialAnswer;
    
            $messages2 = [
                [
                    'role' => 'user',
                    'content' => $prompt2,
                ]
            ];
    
            // Send the second request to OpenAI API
            $response2 = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => $messages2,
                'max_tokens' => 600,
                'temperature' => 0.3, // Lower temperature for a more deterministic and refined output
            ]);
    
            $responseArray2 = $response2->json();
    
            Log::info('OpenAI GPT Correction Response: ' . json_encode($responseArray2));
    
            if (isset($responseArray2['error'])) {
                throw new \Exception($responseArray2['error']['message']);
            }
    
            $correctedAnswer = $responseArray2['choices'][0]['message']['content'] ?? '';
    
            // Check if the response indicates that all sentences are correct
            if (stripos($correctedAnswer, 'all sentences are correct and natural') !== false) {
                return $initialAnswer;  // Return the original content if no corrections are needed
            }
    
            // Return the corrected content if there were any mistakes
            return $correctedAnswer;
    
        } catch (\Exception $e) {
            Log::error('Error with OpenAI API request: ' . $e->getMessage());
            return 'An error occurred while communicating with the OpenAI API.';
        }
    }
    
    

}
