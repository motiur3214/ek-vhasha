Ek Bhasha (এক ভাষা) - AI-Powered Bengali Voice Translator
Ek Bhasha is a simple yet powerful web application built with Laravel that translates spoken Bengali into spoken English, designed to bridge communication gaps. It uses a state-of-the-art AI pipeline to provide accurate, natural-sounding translations with audio output.

Core Features
🎤 Voice Input: Record Bengali speech directly in the browser using the Web Audio API.

🧠 High-Quality Translation: Utilizes a sophisticated AI pipeline for accurate and context-aware translations.

🔊 Audio Output: Listen to the final English translation in a clear, natural voice.

✨ Clean & Simple UI: A minimal, user-friendly interface built with Tailwind CSS.

How It Works
This app uses a powerful three-step AI process to ensure the highest quality translations, leveraging the best model for each specific task:

Speech-to-Text (Transcription): A user's Bengali audio is first transcribed into accurate Bengali text using OpenAI's Whisper model. This allows the system to see what was said.

Text-to-Text Translation: The transcribed text is then translated into English by OpenAI's GPT-4o, their most advanced language model. This step ensures that linguistic nuance and the original meaning are preserved.

Text-to-Speech (Synthesis): The final English text is converted back into high-quality spoken audio using OpenAI's TTS model.

Future Vision
The ultimate goal of Ek Bhasha is to become a universal translator for all Bengali speakers. The next major milestone is to integrate a custom language model that can first translate diverse local dialects and pronunciations into standardized Bengali. This "normalization" step will then be passed to the English translation model, making the tool accessible and accurate for everyone, regardless of their regional dialect.

Technologies Used
Backend: Laravel

Frontend: Blade, Tailwind CSS, JavaScript (Web Audio API)

AI Services: OpenAI API (Whisper, GPT-4o, TTS)

Getting Started
Follow these steps to get the project running on your local machine.

Prerequisites
PHP >= 8.1

Composer

An OpenAI API Key

Installation
Clone the repository:

git clone [https://github.com/your-username/ek-bhasha.git](https://github.com/your-username/ek-bhasha.git)
cd ek-bhasha

Install PHP dependencies:

composer install

Set up your environment file:

cp .env.example .env

Generate an application key:

php artisan key:generate

Add your OpenAI API key to the .env file:

OPENAI_API_KEY="sk-..."

Run the development server:

php artisan serve

Now, open http://127.0.0.1:8000 in your browser to use the application.
