<?php

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Volt\Component;

new class extends Component {
    use WithFileUploads;

    public string $activeTab = 'staff-magika';

    public array $staffAgents = [
        ['key' => 'wan', 'name' => 'Wan', 'role' => 'Ideal Customer Persona'],
        ['key' => 'tina', 'name' => 'Tina', 'role' => 'Fear & Desire'],
        ['key' => 'jamil', 'name' => 'Jamil', 'role' => 'Marketing Angle'],
        ['key' => 'najwa', 'name' => 'Najwa', 'role' => 'Copywriter'],
        ['key' => 'saifuz', 'name' => 'Saifuz', 'role' => 'Copy Variations'],
        ['key' => 'mieya', 'name' => 'Mieya', 'role' => 'Formula Copywriting (AIDA)'],
        ['key' => 'afiq', 'name' => 'Afiq', 'role' => 'Sales Page Creator'],
        ['key' => 'julia', 'name' => 'Julia', 'role' => 'Headline Brainstormer'],
        ['key' => 'mazrul', 'name' => 'Mazrul', 'role' => 'Script Writer'],
        ['key' => 'musa', 'name' => 'Musa', 'role' => 'LinkedIn Branding'],
        ['key' => 'joe', 'name' => 'Joe', 'role' => 'Image Prompter'],
        ['key' => 'zaki', 'name' => 'Zaki', 'role' => 'Poster Prompter'],
    ];

    public string $selectedStaff = 'wan';
    public string $staffInput = '';
    public ?string $staffOutput = null;
    public ?string $staffRawResponse = null;

    public string $contentTopic = '';
    public string $contentLanguage = 'English';
    public array $contentIdeasOutput = [];

    public string $marketingProduct = '';
    public string $marketingAudience = '';
    public string $marketingKeywords = '';
    public string $marketingTone = 'Professional';
    public string $marketingLanguage = 'English';
    public ?array $marketingOutput = null;
    public ?string $marketingRawResponse = null;

    public ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $productPhoto = null;
    public string $productDescription = '';
    public string $storyVibe = 'Random';
    public string $storyLighting = 'Random';
    public string $storyContentType = 'Random';
    public string $storyLanguage = 'English';
    public array $storyOutput = [];
    public ?string $storyRawResponse = null;

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];
    public array $tones = ['Professional', 'Friendly', 'Bold', 'Playful', 'Conversational'];
    public array $storyVibes = ['Random', 'Inspirational', 'Bold', 'Playful', 'Premium'];
    public array $storyLightings = ['Random', 'Bright', 'Moody', 'Natural', 'Studio'];
    public array $storyContentTypes = ['Random', 'Product Ad', 'Founder Story', 'Tutorial', 'Lifestyle'];

    protected string $layout = 'layouts.app';
    protected GeminiService $geminiService;

    public function boot(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function withLayoutData(): array
    {
        return [
            'title' => __('AI Content Idea Suite'),
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function selectStaff(string $staffKey): void
    {
        $this->selectedStaff = $staffKey;
        $this->staffOutput = null;
    }

    public function generateStaffOutput(): void
    {
        $this->validate([
            'staffInput' => ['required', 'string', 'min:10'],
            'contentLanguage' => ['required', 'string', 'in:English,Malay,Chinese,Tamil'],
        ]);

        $agent = collect($this->staffAgents)->firstWhere('key', $this->selectedStaff);
        
        if (!$this->geminiService->testConnection()) {
            session()->flash('error', 'Unable to connect to Gemini API. Please check your API key.');
            return;
        }

        // Build the base prompt with role instructions
        $prompt = $this->buildStaffPrompt($agent);
        
        // Add strict language and role-following instructions
        $prompt .= "\n\n" . implode("\n", [
            "INSTRUCTIONS:",
            "1. You MUST respond in {$this->contentLanguage} language.",
            "2. You MUST maintain your role as {$agent['name']}, {$agent['role']} in your response.",
            "3. Do not include any English text unless it's a proper noun or technical term.",
            "4. Format your response according to the requested structure.",
            "5. If you're unsure how to say something in {$this->contentLanguage}, ask for clarification rather than defaulting to English.",
            "6. Use appropriate cultural context and idioms for {$this->contentLanguage} when relevant."
        ]);
        
        try {
            $response = $this->geminiService->generateContent($prompt, [
                'language' => $this->contentLanguage,
                'temperature' => 0.7,
                'top_p' => 0.9,
            ]);

            if ($response) {
                $this->staffRawResponse = $response;
                $this->staffOutput = $response;
                
                // Log successful generation
                AiActivityLogger::log(
                    activityType: 'staff_output_generated',
                    model: 'gemini-2.5-flash',
                    prompt: $prompt,
                    output: $response,
                    tokenCount: (int)(strlen($response) / 4), // Rough estimate of token count
                    status: 'success',
                    meta: [
                        'agent' => $agent['name'] ?? 'Unknown',
                        'role' => $agent['role'] ?? 'Unknown',
                        'language' => $this->contentLanguage,
                        'tool' => 'Staff Magika',
                        'tab' => $this->activeTab
                    ]
                );
            } else {
                throw new \Exception('Empty response from AI service');
            }
        } catch (\Exception $e) {
            // Log error activity
            AiActivityLogger::log(
                activityType: 'staff_output_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'error',
                errorMessage: $e->getMessage(),
                meta: [
                    'agent' => $agent['name'] ?? 'Unknown',
                    'role' => $agent['role'] ?? 'Unknown',
                    'language' => $this->contentLanguage,
                    'tool' => 'Staff Magika',
                    'tab' => $this->activeTab,
                    'error' => $e->getTraceAsString()
                ]
            );
            
            session()->flash('error', 'Failed to generate content: ' . $e->getMessage());
        }
    }

    protected function buildStaffPrompt(array $agent): string
    {
        $agentId = $agent['key'] ?? '';
        $input = $this->staffInput;
        $language = $this->contentLanguage;
        
        // Common instruction for all agents
        $commonInstructions = [
            'language' => $language,
            'tone' => 'professional but approachable',
            'format' => 'well-structured with clear sections',
            'cultural_context' => 'Use appropriate cultural context and examples relevant to the language and region.'
        ];
        
        switch ($agentId) {
            case 'wan':
                $prompt = "ROLE: You are Wan, an expert in market research with deep knowledge of {$language} speaking markets.\n\n";
                $prompt .= "TASK: Based on the product/service \"{$input}\", create a detailed 'Ideal Customer Persona' in {$language}.\n\n";
                $prompt .= "INCLUDE THESE SECTIONS (in {$language}):\n";
                $prompt .= "1. DEMOGRAPHICS (Age, Gender, Location, Income, etc.)\n";
                $prompt .= "2. INTERESTS AND HOBBIES\n";
                $prompt .= "3. PAIN POINTS AND CHALLENGES\n";
                $prompt .= "4. GOALS AND MOTIVATIONS\n";
                $prompt .= "5. BUYING BEHAVIOR\n";
                $prompt .= "6. PREFERRED COMMUNICATION CHANNELS\n\n";
                $prompt .= "FORMAT: Use a well-organized format with clear headings for each section. Use numbered or bullet points for better readability.";
                break;
                
            case 'tina':
                $prompt = "ROLE: You are Tina, a behavioral psychology expert with expertise in {$language} speaking markets.\n\n";
                $prompt .= "TASK: For the product/service \"{$input}\", analyze and provide the following in {$language}:\n\n";
                $prompt .= "1. KEY FEARS (What the customer wants to avoid)\n";
                $prompt .= "2. CORE DESIRES (What the customer wants to achieve)\n";
                $prompt .= "3. EMOTIONAL TRIGGERS\n";
                $prompt .= "4. PSYCHOLOGICAL BARRIERS TO PURCHASE\n\n";
                $prompt .= "FORMAT: Use clear and understandable language and provide examples relevant to the {$language} context.";
                break;
                
            case 'jamil':
                $prompt = "ROLE: You are Jamil, a marketing strategist specializing in {$language} speaking markets.\n\n";
                $prompt .= "TASK: For the product/service \"{$input}\", provide 3 distinct marketing angles in {$language}:\n\n";
                $prompt .= "Each marketing angle should include (in {$language}):\n";
                $prompt .= "- Target Audience (Who to reach)\n";
                $prompt .= "- Unique Value (What differentiates this product/service)\n";
                $prompt .= "- Key Message (What to communicate)\n";
                $prompt .= "- Potential Channels (Where to promote)\n\n";
                $prompt .= "FORMAT: Use a clear and organized format for each marketing angle.";
                break;
                
            case 'najwa':
                $prompt = "ROLE: You are Najwa, a professional copywriter fluent in {$language}.\n\n";
                $prompt .= "TASK: Write a persuasive marketing copy in {$language} for: \"{$input}\"\n\n";
                $prompt .= "Focus on benefits, not features. Structure your response with:\n";
                $prompt .= "1. ATTENTION-GRABBING HEADLINE\n";
                $prompt .= "2. ENGAGING INTRODUCTION\n";
                $prompt .= "3. KEY BENEFITS (3-5 points)\n";
                $prompt .= "4. COMPELLING CALL-TO-ACTION\n\n";
                $prompt .= "FORMAT: Use persuasive language that aligns with {$language} cultural context.";
                break;
                
            case 'saifuz':
                $prompt = "ROLE: You are Saifuz, an A/B testing specialist with experience in {$language} markets.\n\n";
                $prompt .= "TASK: Create 3 variations of this copy in {$language}:\n\n";
                $prompt .= "Original: {$input}\n\n";
                $prompt .= "For each variation, include (in {$language}):\n";
                $prompt .= "- Variation Name/Theme\n";
                $prompt .= "- Modified Copy\n";
                $prompt .= "- Why This Might Be More Effective\n\n";
                $prompt .= "FORMAT: Ensure each variation has a different approach while maintaining proper {$language} language.";
                break;
                
            case 'mieya':
                $prompt = "ROLE: You are Mieya, an expert in classic marketing formulas, specializing in {$language} copywriting.\n\n";
                $prompt .= "TASK: Write a marketing copy in {$language} for \"{$input}\" using the AIDA formula.\n\n";
                $prompt .= "Structure your response with the following sections in {$language}:\n";
                $prompt .= "1. ATTENTION: Grab attention\n";
                $prompt .= "2. INTEREST: Build interest\n";
                $prompt .= "3. DESIRE: Create desire\n";
                $prompt .= "4. ACTION: Call to action\n\n";
                $prompt .= "FORMAT: Effectively apply the AIDA formula within the context of {$language} language and culture.";
                break;
                
            case 'afiq':
                $prompt = "ROLE: You are Afiq, a web content strategist specializing in {$language} conversion optimization.\n\n";
                $prompt .= "TASK: Outline a high-converting sales page in {$language} for: \"{$input}\"\n\n";
                $prompt .= "INCLUDE THESE SECTIONS (in {$language}):\n";
                $prompt .= "1. HERO SECTION (Headline + Subheadline)\n";
                $prompt .= "2. PROBLEM STATEMENT\n";
                $prompt .= "3. SOLUTION OVERVIEW\n";
                $prompt .= "4. KEY FEATURES/BENEFITS\n";
                $prompt .= "5. TESTIMONIALS/SOCIAL PROOF\n";
                $prompt .= "6. OFFER DETAILS\n";
                $prompt .= "7. STRONG CALL-TO-ACTION\n";
                $prompt .= "8. FAQ SECTION\n\n";
                $prompt .= "FORMAT: Emphasize elements that are effective for {$language}-speaking audiences.";
                break;
                
            case 'julia':
                $prompt = "ROLE: You are Julia, a headline specialist with expertise in {$language} copywriting.\n\n";
                $prompt .= "TASK: Create 10 attention-grabbing headlines in {$language} for: \"{$input}\"\n\n";
                $prompt .= "For each headline, include (in {$language}):\n";
                $prompt .= "1. HEADLINE\n";
                $prompt .= "2. HEADLINE TYPE (e.g., How-to, Question, List, etc.)\n";
                $prompt .= "3. WHY IT WORKS\n\n";
                $prompt .= "FORMAT: Ensure the headlines use proper {$language} grammar and style that is engaging and attention-grabbing.";
                break;
                
            case 'mazrul':
                $prompt = "ROLE: You are Mazrul, a video scriptwriter specializing in {$language} content.\n\n";
                $prompt .= "TASK: Write a 30-60 second social media ad script in {$language} for: \"{$input}\"\n\n";
                $prompt .= "INCLUDE THESE ELEMENTS (in {$language}):\n";
                $prompt .= "1. HOOK (0-5 seconds) - Grab the viewer's attention\n";
                $prompt .= "2. PROBLEM (5-15 seconds) - Identify the viewer's pain point\n";
                $prompt .= "3. SOLUTION (15-30 seconds) - Show how the product/service solves the problem\n";
                $prompt .= "4. CALL-TO-ACTION (30-60 seconds) - Provide clear instructions\n\n";
                $prompt .= "FORMAT: Write the full script in {$language} and provide visual direction for each section in [square brackets]. Use language suitable for short, engaging videos.";
                break;
                
            case 'musa':
                $prompt = "ROLE: You are Musa, a personal branding coach with expertise in {$language} professional communication.\n\n";
                $prompt .= "TASK: Create a compelling personal branding post in {$language} about: \"{$input}\"\n\n";
                $prompt .= "STRUCTURE YOUR RESPONSE WITH THESE SECTIONS (in {$language}):\n";
                $prompt .= "1. ENGAGING INTRODUCTION - Start with an attention-grabbing statement\n";
                $prompt .= "2. PERSONAL STORY - Share a relevant personal experience\n";
                $prompt .= "3. VALUABLE INSIGHT - Provide useful advice or perspective\n";
                $prompt .= "4. CALL-TO-ENGAGEMENT - End with a question or call-to-action\n\n";
                $prompt .= "FORMAT: Use a tone appropriate for professional social media platforms in {$language}.";
                break;
                
            case 'joe':
                $prompt = "ROLE: You are Joe, an AI art prompt engineer with expertise in {$language} visual concepts.\n\n";
                $prompt .= "TASK: Create a detailed prompt in {$language} for generating an image about: \"{$input}\"\n\n";
                $prompt .= "INCLUDE THESE ELEMENTS (in {$language}):\n";
                $prompt .= "1. SUBJECT DESCRIPTION - Clearly explain what should be depicted\n";
                $prompt .= "2. STYLE (e.g., realistic, watercolor, digital art, etc.)\n";
                $prompt .= "3. LIGHTING - Type and direction of light\n";
                $prompt .= "4. COMPOSITION - Arrangement of elements in the image\n";
                $prompt .= "5. COLOR PALETTE - Desired color combinations\n";
                $prompt .= "6. MOOD/EMOTION - Feeling to be conveyed\n\n";
                $prompt .= "FORMAT: Provide clear and detailed instructions in {$language} that are easy for AI to understand.";
                break;
                
            case 'zaki':
                $prompt = "ROLE: You are Zaki, a graphic design expert specializing in {$language} promotional materials.\n\n";
                $prompt .= "TASK: Create a detailed prompt in {$language} for a promotional poster about: \"{$input}\"\n\n";
                $prompt .= "INCLUDE THESE SPECIFICATIONS (in {$language}):\n";
                $prompt .= "1. LAYOUT STRUCTURE - How elements should be arranged\n";
                $prompt .= "2. COLOR SCHEME - Appropriate color combinations\n";
                $prompt .= "3. TYPOGRAPHY - Font types and styles\n";
                $prompt .= "4. KEY VISUAL ELEMENTS - Main images or graphics\n";
                $prompt .= "5. TEXT PLACEMENT - Where and how text should be positioned\n";
                $prompt .= "6. OVERALL STYLE - Theme and overall aesthetic\n\n";
                $prompt .= "FORMAT: Provide clear and detailed instructions in {$language} for creating an effective promotional poster.";
                break;
                
            default:
                $prompt = "Please analyze the following input and provide a helpful response in {$language}:\n\n";
                $prompt .= $input;
                break;
        }
        
        return $prompt;
    }

    protected function generateStaffInsight(string $type): string
    {
        $topic = Str::headline($this->staffInput ?: __('your product or service'));
        return match ($type) {
            'Pain point' => "Customers struggle with {$topic} because it feels overwhelming without clear guidance.",
            'Opportunity' => "{$topic} can stand out by highlighting a quick transformation and tangible proof.",
            default => "Invite the audience to take the next step with a confident and time-bound offer.",
        };
    }

    public function resetStaffForm(): void
    {
        $this->staffInput = '';
        $this->staffOutput = null;
        $this->staffRawResponse = null;
    }

    public function generateContentIdeas(): void
    {
        $this->validate([
            'contentTopic' => ['required', 'string', 'min:4'],
            'contentLanguage' => ['required', 'string', 'in:English,Malay,Chinese,Tamil'],
        ]);

        if (!$this->geminiService->testConnection()) {
            $this->addError('contentTopic', 'Unable to connect to Gemini API. Please check your API key.');
            return;
        }

        $prompt = $this->buildContentIdeasPrompt();
        $startTime = microtime(true);
        $response = $this->geminiService->generateContent($prompt);
        $latencyMs = (int)((microtime(true) - $startTime) * 1000);

        if ($response) {
            $this->parseContentIdeasResponse($response);
            
            // Log successful generation
            AiActivityLogger::log(
                activityType: 'content_ideas_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                output: json_encode($this->contentIdeasOutput, JSON_PRETTY_PRINT),
                tokenCount: mb_strlen($prompt) + mb_strlen($response),
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'topic' => $this->contentTopic,
                    'language' => $this->contentLanguage,
                    'ideas_count' => count($this->contentIdeasOutput)
                ]
            );
        } else {
            $errorMessage = 'Failed to generate content ideas';
            $this->addError('contentTopic', $errorMessage);
            
            // Log failure
            AiActivityLogger::log(
                activityType: 'content_ideas_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'failed',
                errorMessage: $errorMessage,
                latencyMs: $latencyMs,
                meta: [
                    'topic' => $this->contentTopic,
                    'language' => $this->contentLanguage
                ]
            );
        }
    }

    protected function buildContentIdeasPrompt(): string
    {
        return "Generate 4 engaging content ideas about: {$this->contentTopic}\n\n" .
               "For each idea, provide:\n" .
               "1. A catchy title (max 10 words)\n" .
               "2. A unique angle or perspective (1-2 sentences)\n" .
               "3. A hook to capture attention (1 sentence)\n\n" .
               "Format the response as a numbered list with each idea separated by two newlines.\n" .
               "Output in {$this->contentLanguage} language with a {$this->marketingTone} tone.\n" .
               "IMPORTANT: All output must be in {$this->contentLanguage} language, including all titles, angles, and hooks.";
    }

    protected function parseContentIdeasResponse(string $response): void
    {
        $ideas = [];
        $lines = explode("\n", $response);
        $lines = array_filter($lines, function($line) {
            return !empty($line);
        });
        $currentIdea = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($currentIdea)) {
                    $ideas[] = $currentIdea;
                    $currentIdea = [];
                }
                continue;
            }

            if (preg_match('/^\d+\.\s*(.+)/', $line, $matches)) {
                if (!empty($currentIdea)) {
                    $ideas[] = $currentIdea;
                }
                $currentIdea = ['title' => $matches[1]];
            } elseif (preg_match('/^[A-Za-z\s]+:/', $line)) {
                // Skip section headers
                continue;
            } elseif (!empty($currentIdea)) {
                if (!isset($currentIdea['angle'])) {
                    $currentIdea['angle'] = $line;
                } elseif (!isset($currentIdea['hook'])) {
                    $currentIdea['hook'] = $line;
                }
            }
        }

        if (!empty($currentIdea)) {
            $ideas[] = $currentIdea;
        }

        $this->contentIdeasOutput = array_slice($ideas, 0, 5);
    }

    public function resetContentIdeas(): void
    {
        $this->contentTopic = '';
        $this->contentLanguage = 'English';
        $this->contentIdeasOutput = [];
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
                status: 'failed',
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
        } else {
            $errorMessage = 'Failed to generate marketing copy';
            $this->addError('marketingProduct', $errorMessage);
            
            // Log failure
            AiActivityLogger::log(
                activityType: 'marketing_copy_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'failed',
                errorMessage: $errorMessage,
                latencyMs: $latencyMs,
                meta: [
                    'product' => substr($this->marketingProduct, 0, 100),
                    'tone' => $this->marketingTone,
                    'audience' => $this->marketingAudience,
                    'language' => $this->marketingLanguage
                ]
            );
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

    public function generateStoryline(): void
    {
        $this->validate([
            'productPhoto' => ['nullable', 'image', 'max:5120'],
            'productDescription' => ['required', 'string', 'min:10'],
        ]);

        if (!$this->geminiService->testConnection()) {
            $this->addError('productDescription', 'Unable to connect to Gemini API. Please check your API key.');
            return;
        }

        $prompt = $this->buildStorylinePrompt();
        $response = $this->geminiService->generateContent($prompt);

        if ($response) {
            $this->storyRawResponse = $response;
            $this->parseStorylineResponse($response);
        } else {
            $this->addError('productDescription', 'Failed to generate storyline. Please try again.');
        }
    }

    protected function buildStorylinePrompt(): string
    {
        $vibe = $this->storyVibe !== 'Random' ? " with a {$this->storyVibe} vibe" : '';
        $lighting = $this->storyLighting !== 'Random' ? " with {$this->storyLighting} lighting" : '';
        $contentType = $this->storyContentType !== 'Random' ? " in the style of a {$this->storyContentType}" : '';

        return "Create a 3-scene video ad concept for: {$this->productDescription}\n\n" .
               "**Style:**{$vibe}{$lighting}{$contentType}\n" .
               "**Language:** {$this->storyLanguage}\n\n" .
               "For each scene, provide:\n" .
               "1. A brief visual description\n" .
               "2. Suggested camera angles/movements\n" .
               "3. Key text or voiceover points\n\n" .
               "Format as a numbered list with clear scene separators.";
    }

    protected function parseStorylineResponse(string $response): void
    {
        $scenes = [];
        $currentScene = [];
        $sceneNumber = 1;
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Look for scene markers (e.g., "Scene 1:", "1.", etc.)
            if (preg_match('/^(?:Scene\s*)?(\d+)[:\.]\s*(.+)?/i', $line, $matches)) {
                if (!empty($currentScene)) {
                    $scenes[] = $this->formatScene($currentScene, $sceneNumber++);
                }
                $currentScene = [
                    'label' => __('Scene :number', ['number' => $matches[1]]),
                    'description' => $matches[2] ?? '',
                    'details' => []
                ];
            } elseif (!empty($currentScene)) {
                // Add details to the current scene
                if (preg_match('/-\s*(.+)/', $line, $detailMatch)) {
                    $currentScene['details'][] = $detailMatch[1];
                } elseif (!empty($line)) {
                    $currentScene['description'] .= (empty($currentScene['description']) ? '' : ' ') . $line;
                }
            }
        }

        // Add the last scene if it exists
        if (!empty($currentScene)) {
            $scenes[] = $this->formatScene($currentScene, $sceneNumber);
        }

        // Ensure we have at least 3 scenes
        while (count($scenes) < 3) {
            $scenes[] = [
                'label' => __('Scene :number', ['number' => count($scenes) + 1]),
                'description' => __('Scene description will be generated here.'),
                'details' => []
            ];
        }

        $this->storyOutput = array_slice($scenes, 0, 3);
    }

    protected function formatScene(array $scene, int $number): array
    {
        $description = trim($scene['description']);
        
        // Add details to the description if available
        if (!empty($scene['details'])) {
            $description .= "\n\n" . implode("\n", array_map(fn($d) => "• {$d}", $scene['details']));
        }

        return [
            'label' => __('Scene :number', ['number' => $number]),
            'description' => $description,
        ];
    }

    public function resetStoryline(): void
    {
        $this->reset('productPhoto');
        $this->productDescription = '';
        $this->storyVibe = 'Random';
        $this->storyLighting = 'Random';
        $this->storyContentType = 'Random';
        $this->storyLanguage = 'English';
        $this->storyOutput = [];
        $this->storyRawResponse = null;
    }
}; ?>

<div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/30">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</h3>
                </div>
            </div>
        </div>
    @endif
    
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Content Idea Suite') }}</h1>
        <p class="max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Discover ideas, craft marketing assets, and storyboard product ads with a collaborative team of AI specialists.') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-2">
            @foreach ([
                'staff-magika' => __('Magika Persona'),
                'content-ideas' => __('Content Ideas'),
                'marketing-copy' => __('Marketing Copy'),
                // 'product-storyline' => __('Product Ad Storyline'),
            ] as $tabKey => $label)
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $tabKey }}')"
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500',
                        'bg-zinc-900 text-white shadow-sm dark:bg-zinc-100 dark:text-zinc-900' => $activeTab === $tabKey,
                        'bg-transparent text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' => $activeTab !== $tabKey,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px,1fr] xl:grid-cols-[380px,1fr]">
        @if ($activeTab === 'staff-magika')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Magika Persona') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Your expert AI team for marketing tasks. Select an agent and describe the task to get personalised insights.') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-4 gap-3">
                        @foreach ($staffAgents as $agent)
                            <button
                                type="button"
                                wire:click="selectStaff('{{ $agent['key'] }}')"
                                @class([
                                    'flex flex-col rounded-lg border px-4 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500',
                                    'border-zinc-900 bg-zinc-50 text-zinc-900 shadow-sm dark:border-zinc-500 dark:bg-zinc-800 dark:text-zinc-100' => $selectedStaff === $agent['key'],
                                    'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' => $selectedStaff !== $agent['key'],
                                ])
                            >
                                <span class="text-sm font-semibold">{{ $agent['name'] }}</span>
                                <span class="mt-1 text-xs text-inherit/70">{{ $agent['role'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="staff-input">{{ __('Input for agent') }}</label>
                        <textarea
                            id="staff-input"
                            wire:model.defer="staffInput"
                            rows="4"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('Describe your product or campaign...') }}"
                        ></textarea>
                        @error('staffInput')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-language">{{ __('Output Language') }}</label>
                            <select
                                id="content-language"
                                wire:model="contentLanguage"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach ($languages as $language)
                                    <option value="{{ $language }}">{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateStaffOutput"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <span wire:loading.remove wire:target="generateStaffOutput">{{ __('Generate Insights') }}</span>
                            <span wire:loading wire:target="generateStaffOutput">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetStaffForm"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                        @if ($staffRawResponse)
                            <div class="flex space-x-2">
                                <button 
                                    type="button" 
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText(@js($staffRawResponse));
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                                <button 
                                    type="button"
                                    x-data="{ saved: false }"
                                    x-on:click="
                                        const blob = new Blob([@js($staffRawResponse)], { type: 'text/plain' });
                                        const url = URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = 'ai-output-' + new Date().toISOString().slice(0, 10) + '.txt';
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                        URL.revokeObjectURL(url);
                                        saved = true;
                                        setTimeout(() => saved = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!saved" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="saved ? 'Saved!' : 'Save as TXT'"></span>
                                </button>
                            </div>
                        @endif
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if ($staffRawResponse)
                            <pre class="whitespace-pre-wrap break-words font-mono text-sm">{{ $staffRawResponse }}</pre>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.sparkles variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('The AI\'s response will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'content-ideas')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Content Idea Generator') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Discover trending and engaging content ideas for any topic using fresh search data.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-topic">{{ __('Your Topic or Niche') }}</label>
                            <textarea
                                id="content-topic"
                                wire:model.defer="contentTopic"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., digital marketing for small business or healthy breakfast recipes') }}"
                            ></textarea>
                            @error('contentTopic')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-language">{{ __('Output Language') }}</label>
                            <select
                                id="content-language"
                                wire:model="contentLanguage"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach ($languages as $language)
                                    <option value="{{ $language }}">{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateContentIdeas"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <span wire:loading.remove wire:target="generateContentIdeas">{{ __('Generate Ideas') }}</span>
                            <span wire:loading wire:target="generateContentIdeas">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetContentIdeas"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                        @if (count($contentIdeasOutput) > 0)
                            <div class="flex space-x-2">
                                <button 
                                    type="button" 
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        const outputText = @js(collect($contentIdeasOutput)->map(function($idea) {
                                            return "# " . ($idea['title'] ?? 'Untitled Idea') . "\n" . 
                                                   (isset($idea['angle']) ? $idea['angle'] . "\n" : '') . 
                                                   (isset($idea['hook']) ? '\"' . $idea['hook'] . '\"' : '');
                                        })->filter()->join('\n\n'));
                                        navigator.clipboard.writeText(outputText);
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy All'"></span>
                                </button>
                                <button 
                                    type="button"
                                    x-data="{ saved: false }"
                                    x-on:click="
                                        const content = @js(collect($contentIdeasOutput)->map(function($idea) {
                                            return "# " . ($idea['title'] ?? 'Untitled Idea') . "\n" . 
                                                   (isset($idea['angle']) ? $idea['angle'] . "\n" : '') . 
                                                   (isset($idea['hook']) ? '\"' . $idea['hook'] . '\"' : '');
                                        })->filter()->join('\n\n'));
                                        const blob = new Blob([content], { type: 'text/plain' });
                                        const url = URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = 'content-ideas-' + new Date().toISOString().slice(0, 10) + '.txt';
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                        URL.revokeObjectURL(url);
                                        saved = true;
                                        setTimeout(() => saved = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!saved" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 012 2h14a2 2 0 012-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="saved ? 'Saved!' : 'Save All as TXT'"></span>
                                </button>
                            </div>
                        @endif
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if (count($contentIdeasOutput) > 0)
                            <div class="space-y-4">
                                @foreach ($contentIdeasOutput as $index => $idea)
                                    <div class="group relative rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $idea['title'] ?? 'Untitled Idea' }}</h3>
                                        @if(isset($idea['angle']))
                                            <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $idea['angle'] }}</p>
                                        @endif
                                        @if(isset($idea['hook']))
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 italic">"{{ $idea['hook'] }}"</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.speaker-wave variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Generate content ideas to see the output here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'marketing-copy')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Marketing Copywriter') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Generate persuasive copy for ads, posts, and websites with customizable tone and language.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-product">{{ __('Product/Service Details') }}</label>
                            <textarea
                                id="marketing-product"
                                wire:model.defer="marketingProduct"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., a high-end coffee maker that brews in 30 seconds...') }}"
                            ></textarea>
                            @error('marketingProduct')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-audience">{{ __('Target Audience (Optional)') }}</label>
                                <input
                                    id="marketing-audience"
                                    type="text"
                                    wire:model.defer="marketingAudience"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('e.g., busy professionals, coffee lovers...') }}"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-keywords">{{ __('Keywords to Include (Optional)') }}</label>
                                <input
                                    id="marketing-keywords"
                                    type="text"
                                    wire:model.defer="marketingKeywords"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('e.g., quick, premium, morning coffee') }}"
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-tone">{{ __('Tone of Voice') }}</label>
                                <select
                                    id="marketing-tone"
                                    wire:model="marketingTone"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($tones as $tone)
                                        <option value="{{ $tone }}">{{ $tone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-language">{{ __('Output Language') }}</label>
                                <select
                                    id="marketing-language"
                                    wire:model="marketingLanguage"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($languages as $language)
                                        <option value="{{ $language }}">{{ $language }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateMarketingCopy"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <span wire:loading.remove wire:target="generateMarketingCopy">{{ __('Generate Copy') }}</span>
                            <span wire:loading wire:target="generateMarketingCopy">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetMarketingCopy"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                        @if ($marketingRawResponse)
                            <div class="flex space-x-2">
                                <button 
                                    type="button" 
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText(@js($marketingRawResponse));
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                                <button 
                                    type="button"
                                    x-data="{ saved: false }"
                                    x-on:click="
                                        const blob = new Blob([@js($marketingRawResponse)], { type: 'text/plain' });
                                        const url = URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = 'marketing-copy-' + new Date().toISOString().slice(0, 10) + '.txt';
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                        URL.revokeObjectURL(url);
                                        saved = true;
                                        setTimeout(() => saved = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!saved" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 012 2h14a2 2 0 012-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="saved ? 'Saved!' : 'Save as TXT'"></span>
                                </button>
                            </div>
                        @endif
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if ($marketingRawResponse)
                            <pre class="whitespace-pre-wrap break-words font-mono text-sm">{{ $marketingRawResponse }}</pre>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.speaker-wave variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your generated marketing copy will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Product Ad Storyline') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Generate a short, punchy video ad concept. Upload an image and describe your product to build a 1-scene storyline.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-photo">{{ __('Upload Product Photo') }}</label>
                            <label
                                for="product-photo"
                                class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-sm text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200"
                            >
                                @if ($productPhoto)
                                    <img src="{{ $productPhoto->temporaryUrl() }}" alt="{{ __('Uploaded preview') }}" class="h-32 w-32 rounded-lg object-cover shadow-sm" />
                                    <span class="mt-3 text-xs text-zinc-400">{{ __('Click to change image') }}</span>
                                @else
                                    <flux:icon.photo variant="outline" class="mb-3 size-10 text-zinc-300" />
                                    <span class="text-sm font-medium">{{ __('Upload image') }}</span>
                                    <span class="mt-1 text-xs text-zinc-400">{{ __('PNG or JPG up to 5MB') }}</span>
                                @endif
                            </label>
                            <input id="product-photo" type="file" class="hidden" wire:model="productPhoto" accept="image/*" />
                            @error('productPhoto')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-description">{{ __('Product Description') }}</label>
                            <textarea
                                id="product-description"
                                wire:model.defer="productDescription"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., Organic coffee beans from Brazil, single-origin, rich aroma...') }}"
                            ></textarea>
                            @error('productDescription')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-vibe">{{ __('Vibe / Mood') }}</label>
                                <select
                                    id="story-vibe"
                                    wire:model="storyVibe"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyVibes as $vibe)
                                        <option value="{{ $vibe }}">{{ $vibe }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-lighting">{{ __('Lighting') }}</label>
                                <select
                                    id="story-lighting"
                                    wire:model="storyLighting"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyLightings as $lighting)
                                        <option value="{{ $lighting }}">{{ $lighting }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-content-type">{{ __('Content Type') }}</label>
                                <select
                                    id="story-content-type"
                                    wire:model="storyContentType"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyContentTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-language">{{ __('Output Language') }}</label>
                                <select
                                    id="story-language"
                                    wire:model="storyLanguage"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($languages as $language)
                                        <option value="{{ $language }}">{{ $language }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateStoryline"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <span wire:loading.remove wire:target="generateStoryline">{{ __('Generate Storyline') }}</span>
                            <span wire:loading wire:target="generateStoryline">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetStoryline"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                        @if ($storyOutput)
                            <div class="flex space-x-2">
                                <button 
                                    type="button" 
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        const outputText = @js(collect($storyOutput)->map(fn($scene) => $scene['label'] . "\n" . $scene['description'])->join('\n\n'));
                                        navigator.clipboard.writeText(outputText);
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy All'"></span>
                                </button>
                                <button 
                                    type="button"
                                    x-data="{ saved: false }"
                                    x-on:click="
                                        const content = @js(collect($storyOutput)->map(fn($scene) => $scene['label'] . "\n" . $scene['description'])->join('\n\n'));
                                        const blob = new Blob([content], { type: 'text/plain' });
                                        const url = URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = 'storyline-' + new Date().toISOString().slice(0, 10) + '.txt';
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                        URL.revokeObjectURL(url);
                                        saved = true;
                                        setTimeout(() => saved = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!saved" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="saved ? 'Saved!' : 'Save All as TXT'"></span>
                                </button>
                            </div>
                        @endif
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if ($storyOutput)
                            <div class="grid gap-4">
                                @foreach ($storyOutput as $scene)
                                    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $scene['label'] }}</h3>
                                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $scene['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                    @else
                        <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                            <flux:icon.video-camera variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your generated storyboard will appear here.') }}</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
