<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bangla to English Voice Translator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .pulse {
            animation: pulse-animation 1.5s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 w-full max-w-2xl text-center">
        <!-- Header -->
        <h1 class="text-4xl md:text-5xl font-bold text-gray-800">Ek Vhasha (এক ভাষা)</h1>
        <p class="text-lg text-gray-600 mt-2 mb-8">আপনার বাংলা থেকে ইংরেজি অনুবাদক</p>

        <!-- Record Button -->
        <div class="mb-8">
            <button id="recordButton" class="bg-blue-500 hover:bg-blue-600 text-white rounded-full w-24 h-24 flex items-center justify-center mx-auto transition-all duration-300 ease-in-out transform focus:outline-none focus:ring-4 focus:ring-blue-300">
                <svg id="micIcon" class="w-10 h-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 016 0v8.25a3 3 0 01-3 3z" />
                </svg>
            </button>
        </div>

        <!-- Status and Result Display -->
        <div class="bg-gray-50 rounded-lg p-6 min-h-[180px] flex flex-col justify-center">
            <p id="status" class="text-gray-600 font-medium mb-2">Awaiting audio...</p>
            <p id="result" class="text-2xl font-semibold text-gray-900 mb-4"></p>
            <!-- Audio player will be dynamically shown here -->
            <div id="audioPlayerContainer" class="mt-2 hidden">
                <audio controls id="audioPlayer" class="w-full"></audio>
            </div>
        </div>

    </div>

    <script>
        // DOM Element References
        const recordButton = document.getElementById('recordButton');
        const statusEl = document.getElementById('status');
        const resultEl = document.getElementById('result');
        const audioPlayerContainer = document.getElementById('audioPlayerContainer');
        const audioPlayer = document.getElementById('audioPlayer');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // App State
        let isRecording = false;
        let mediaRecorder;
        let audioChunks = [];

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            statusEl.textContent = "Your browser does not support audio recording.";
            recordButton.disabled = true;
        }

        recordButton.addEventListener('click', async () => {
            if (isRecording) {
                stopRecording();
            } else {
                await startRecording();
            }
        });

        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = event => audioChunks.push(event.data);
                mediaRecorder.onstop = sendAudioToServer;
                audioChunks = [];
                mediaRecorder.start();
                isRecording = true;
                updateUIRecording();
            } catch (err) {
                console.error("Error accessing microphone:", err);
                statusEl.textContent = "Microphone access denied. Please allow access in your browser settings.";
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                isRecording = false;
                updateUIProcessing();
            }
        }

        function updateUIRecording() {
            statusEl.textContent = 'Recording... Click to stop.';
            resultEl.textContent = '';
            audioPlayerContainer.classList.add('hidden');
            recordButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            recordButton.classList.add('bg-red-500', 'hover:bg-red-600', 'pulse');
        }

        function updateUIProcessing() {
            statusEl.textContent = 'Translating and generating audio...';
            recordButton.classList.remove('bg-red-500', 'hover:bg-red-600', 'pulse');
            recordButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
            recordButton.disabled = true;
        }

        async function sendAudioToServer() {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');

            try {
                const response = await fetch('/translate-voice', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    console.error('Server returned an error:', data);
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                }

                if (data.error && !data.translation) {
                    resultEl.textContent = data.error;
                } else {
                    statusEl.textContent = 'Translation complete!';
                    resultEl.textContent = data.translation; // Display the translated text

                    if (data.audio_base64) {
                        // Create a playable URL from the base64 string and play the audio
                        const audioUrl = `data:audio/mpeg;base64,${data.audio_base64}`;
                        audioPlayer.src = audioUrl;
                        audioPlayerContainer.classList.remove('hidden');
                        audioPlayer.play();
                    }

                    if(data.error && data.translation){
                        // Handle the case where TTS failed but we still have text
                        statusEl.textContent = data.error;
                    }
                }

            } catch (error) {
                console.error('Error during fetch operation:', error);
                statusEl.textContent = 'An error occurred.';
                resultEl.textContent = 'Could not process request. See browser console (F12) for details.';
            } finally {
                recordButton.disabled = false;
            }
        }
    </script>
</body>
</html>

