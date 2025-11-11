<?php

namespace App\Livewire\AiContentIdeaSuite;

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Illuminate\Support\Str;
use Livewire\Component;

class StaffMagika extends Component
{
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
    public string $contentLanguage = 'English';
    public ?string $staffOutput = null;
    public ?string $staffRawResponse = null;

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];

    protected GeminiService $geminiService;

    public function boot(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
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
                    ]
                );

                session()->flash('message', __('Content generated successfully.'));
            } else {
                throw new \Exception('Empty response from AI service. Please check your API key in Settings > API Keys and ensure it has sufficient quota.');
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

    public function resetStaffForm(): void
    {
        $this->staffInput = '';
        $this->staffOutput = null;
        $this->staffRawResponse = null;
    }

    public function render()
    {
        return view('livewire.ai-content-idea-suite.staff-magika');
    }
}
