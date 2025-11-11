<?php

namespace App\Livewire\AiContentIdeaSuite;

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Illuminate\Support\Str;
use Livewire\Component;

class MarketingCopy extends Component
{
    public string $marketingProduct = '';
    public string $marketingAudience = '';
    public string $marketingKeywords = '';
    public string $marketingTone = 'Professional';
    public string $marketingLanguage = 'English';
    public ?array $marketingOutput = null;
    public ?string $marketingRawResponse = null;

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];
    public array $tones = ['Professional', 'Friendly', 'Bold', 'Playful', 'Conversational'];

    protected GeminiService $geminiService;

    public function boot(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateMarketingCopy(): void
    {
        $this->validate([
            'marketingProduct' => ['required', 'string', 'min:6'],
            'marketingTone' => ['required', 'string'],
        ]);

        if (!$this->geminiService->testConnection()) {
            $errorMessage = 'Unable to connect to Gemini API';
            $this->addError('marketingProduct', $errorMessage);

            // Log connection failure
            AiActivityLogger::log(
                activityType: 'marketing_copy_generated',
                status: 'error',
                errorMessage: $errorMessage,
                meta: [
                    'product' => substr($this->marketingProduct, 0, 100),
                    'tone' => $this->marketingTone,
                    'audience' => $this->marketingAudience,
                    'language' => $this->marketingLanguage
                ]
            );
            return;
        }

        $prompt = $this->buildMarketingCopyPrompt();
        $startTime = microtime(true);

        try {
            $response = $this->geminiService->generateContent($prompt);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            if ($response) {
                $this->marketingRawResponse = $response;
                $this->parseMarketingCopyResponse($response);

                // Log successful generation
                AiActivityLogger::log(
                    activityType: 'marketing_copy_generated',
                    model: 'gemini-2.5-flash',
                    prompt: $prompt,
                    output: $response,
                    tokenCount: mb_strlen($prompt) + mb_strlen($response),
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'product' => substr($this->marketingProduct, 0, 100),
                        'tone' => $this->marketingTone,
                        'audience' => $this->marketingAudience,
                        'language' => $this->marketingLanguage,
                        'keywords' => $this->marketingKeywords
                    ]
                );

                session()->flash('message', __('Marketing copy generated successfully.'));
            } else {
                throw new \Exception('Failed to generate marketing copy');
            }
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            $this->addError('marketingProduct', $errorMessage);

            // Log failure
            AiActivityLogger::log(
                activityType: 'marketing_copy_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'error',
                errorMessage: $errorMessage,
                latencyMs: $latencyMs,
                meta: [
                    'product' => substr($this->marketingProduct, 0, 100),
                    'tone' => $this->marketingTone,
                    'audience' => $this->marketingAudience,
                    'language' => $this->marketingLanguage
                ]
            );

            session()->flash('error', 'Failed to generate marketing copy: ' . $errorMessage);
        }
    }

    protected function buildMarketingCopyPrompt(): string
    {
        $audience = $this->marketingAudience ?: 'General Audience';
        $keywords = $this->marketingKeywords ?: 'None';
        $tone = $this->marketingTone ?: 'Professional';
        $language = $this->marketingLanguage ?: 'English';

        return "You are an expert marketing copywriter. Generate compelling marketing copy based on the following details.
        The final output language must be strictly in {$language}.

        **Product/Service Details:**
        {$this->marketingProduct}

        **Target Audience:**
        {$audience}

        **Tone of Voice:**
        {$tone}

        **Keywords to include:**
        {$keywords}

        **Output Format:**
        1. Start with a compelling HEADLINE (wrapped in ## HEADLINE)
        2. Follow with the BODY content
        3. End with a strong CALL-TO-ACTION (wrapped in **CTA:**)
        4. Include relevant HASHTAGS at the end (wrapped in ## HASHTAGS)

        IMPORTANT:
        - The CTA should be a single, clear, action-oriented sentence and must be in {$language} language.
        - Include 3-5 relevant hashtags based on the content.
        - All output must be in {$language} language.
        - Ensure the content is engaging and matches the specified tone.";
    }

    protected function parseMarketingCopyResponse(string $response): void
    {
        $output = [
            'headline' => '',
            'body' => '',
            'cta' => '',
            'hashtags' => []
        ];

        // Split the response into lines and trim them
        $lines = array_map('trim', explode("\n", $response));
        $lines = array_filter($lines, function($line) {
            return !empty($line);
        });

        // Try to find the headline (first non-empty line that's not a section header)
        foreach ($lines as $line) {
            if (!preg_match('/^#+\s*\w+/i', $line)) { // Skip section headers
                $output['headline'] = $line;
                break;
            }
        }

        // The rest is considered the body
        $bodyLines = array_slice($lines, 1);
        $output['body'] = implode("\n\n", $bodyLines);

        // Try to extract CTA - look for action-oriented phrases in the last few lines
        $ctaCandidates = array_slice($bodyLines, -3); // Check last 3 lines for CTA
        foreach ($ctaCandidates as $line) {
            if (preg_match('/(?:call to action|cta|visit|learn more|sign up|contact us|get started|shop now|buy now|discover|explore|try now|join now|order now)/i', $line)) {
                $output['cta'] = $line;
                break;
            }
        }

        // If no CTA found, use the last line as CTA if it's not too long
        if (empty($output['cta']) && !empty($bodyLines)) {
            $lastLine = end($bodyLines);
            if (strlen($lastLine) < 100 && !preg_match('/[.!?]\s*$/', $lastLine)) {
                $output['cta'] = $lastLine;
            }
        }

        // Extract hashtags from the entire response
        if (preg_match_all('/#(\w+)/', $response, $matches)) {
            $output['hashtags'] = array_unique($matches[1]);
            $output['hashtags'] = array_slice($output['hashtags'], 0, 5); // Limit to 5 hashtags
        }

        // Fallback to product name if no headline
        if (empty($output['headline']) && !empty($this->marketingProduct)) {
            $output['headline'] = Str::headline($this->marketingProduct);
        }

        // Generate a CTA if none found
        if (empty($output['cta'])) {
            $ctaOptions = [
                __('Get started with :product today!', ['product' => $this->marketingProduct]),
                __('Discover the power of :product!', ['product' => $this->marketingProduct]),
                __('Try :product now!', ['product' => $this->marketingProduct]),
                __('Experience :product today!', ['product' => $this->marketingProduct]),
                __('Start your journey with :product!', ['product' => $this->marketingProduct])
            ];
            $output['cta'] = $ctaOptions[array_rand($ctaOptions)];
        }

        // Ensure we have some hashtags
        if (empty($output['hashtags'])) {
            $keywords = !empty($this->marketingKeywords)
                ? array_filter(array_map('trim', explode(',', $this->marketingKeywords)))
                : [];
            $defaultTags = [
                'marketing', 'digitalmarketing', 'business', 'growth', 'success',
                'entrepreneur', 'startup', 'branding', 'socialmedia', 'contentmarketing'
            ];
            $output['hashtags'] = array_slice(array_merge($keywords, $defaultTags), 0, 5);
        }

        $this->marketingOutput = $output;
    }

    public function resetMarketingCopy(): void
    {
        $this->marketingProduct = '';
        $this->marketingAudience = '';
        $this->marketingKeywords = '';
        $this->marketingTone = 'Professional';
        $this->marketingLanguage = 'English';
        $this->marketingOutput = null;
        $this->marketingRawResponse = null;
    }

    public function render()
    {
        return view('livewire.ai-content-idea-suite.marketing-copy');
    }
}
