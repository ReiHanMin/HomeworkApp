<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OCRController extends Controller
{
    public function processImage(Request $request)
    {
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json(['error' => 'Invalid or no image file provided.'], 400);
        }

        try {
            // Initialize Google Vision API client
            $imageAnnotator = new ImageAnnotatorClient();
            $image = file_get_contents($request->file('image')->getRealPath());

            // Perform text detection on the image
            $response = $imageAnnotator->textDetection($image);
            $texts = $response->getTextAnnotations();
            $detectedText = isset($texts[0]) ? $texts[0]->getDescription() : '';

            // Log the detected text for debugging
            Log::info('Google Vision Detected Text: ' . $detectedText);

            // Close the client connection
            $imageAnnotator->close();

            // Analyze the detected text using OpenAI
            $gptResponse = $this->analyzeTextWithOpenAI($detectedText);

            // Return the detected text and the OpenAI response
            return response()->json(['text' => $detectedText, 'gptResponse' => $gptResponse]);

        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the image.'], 500);
        }
    }

    public function processText(Request $request)
    {
        $inputText = $request->input('text');

        if (empty($inputText)) {
            return response()->json(['error' => 'No text provided.'], 400);
        }

        try {
            // Log the manually inputted text for debugging
            Log::info('Manually Inputted Text: ' . $inputText);

            // Analyze the input text using OpenAI
            $gptResponse = $this->analyzeTextWithOpenAI($inputText);

            // Return the input text and the OpenAI response
            return response()->json(['gptResponse' => $gptResponse]);

        } catch (\Exception $e) {
            Log::error('Error processing text: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the text.'], 500);
        }
    }

    private function analyzeTextWithOpenAI($text)
    {
        // Construct the custom prompt
        $prompt = "You are a Japanese language teacher. Provide five example sentences that are grammatically correct in Japanese, based on the following homework question. Each example should be simple, appropriate for a beginner or intermediate learner, and must follow proper Japanese grammar rules. Also, provide an English translation and a brief explanation of the grammar being used in each sentence.\n\nHomework Question:\n" . $text . "\n\nExample sentences:";

        // Construct the message array for the chat completion API
        $messages = [
            [
                'role' => 'user',
                'content' => $prompt,
            ]
        ];

        try {
            // Send the request to the OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'), // Ensure you have your API key set in .env
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4', // Specify GPT-4 or any other model you are using
                'messages' => $messages,
                'max_tokens' => 600, // Increased token limit to accommodate both languages
                'temperature' => 0.7,
            ]);

            // Decode the response body
            $body = $response->getBody();
            $responseArray = json_decode($body, true);

            // Log the response from OpenAI for debugging
            Log::info('OpenAI GPT Response: ' . json_encode($responseArray));

            // Return the generated content
            return $responseArray['choices'][0]['message']['content'] ?? '';

        } catch (\Exception $e) {
            Log::error('Error with OpenAI API request: ' . $e->getMessage());
            return 'An error occurred while communicating with the OpenAI API.';
        }
    }
}
