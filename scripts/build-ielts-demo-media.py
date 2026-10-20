#!/usr/bin/env python3
"""Build original, offline IELTS showcase media; requires espeak-ng, ffmpeg and Pillow."""
import hashlib
import json
import subprocess
import tempfile
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'public/demo/ielts-65'
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
UNITS = json.loads(subprocess.check_output(['php', '-r', 'echo json_encode(require "database/seeders/AiTutorDemo/data/ielts-65-showcase.php");'], cwd=ROOT))
SLIDES = [
    ('listening', 'Listening | Course Registration', [
        ('Before you listen', ['Read the questions first', 'Predict: time, room or deadline', 'Listen for corrections'], 'Before listening, read the questions and predict the type of information you need. Is it a time, a room number, or a registration deadline?'),
        ('Listen for changes', ['Normal start: six oclock', 'Next Tuesday: half past six', 'But signals a correction'], 'The normal starting time is six, but next Tuesday the class begins at half past six. Listen carefully for words that signal a change.'),
        ('Check the details', ['Room twelve, second floor', 'Fee: eighty pounds', 'Register by Friday'], 'Maya should go to room twelve on the second floor. The course fee is eighty pounds. She may pay on the first day, but must register by Friday.'),
    ]),
    ('reading', 'Reading | Libraries', [
        ('Find the main idea', ['Skim each paragraph', 'Name its purpose', 'Avoid reading word by word'], 'Skim each paragraph to find its purpose. The passage describes new library services, an online reservation system, and challenges with digital access.'),
        ('Find supporting details', ['Free laptops and language clubs', 'Study rooms: two hours per day', 'Online booking reduces queues'], 'Scan for the requested details. Library services are free. Study room reservations are limited to two hours per person each day. Online booking has reduced queues.'),
        ('Explain the limitation', ['Some visitors prefer staff help', 'Internet access can be unreliable', 'The library keeps a staffed desk'], 'Digital services do not suit everyone. Some visitors prefer help from a librarian, while others have unreliable internet access. This explains why the booking desk remains available.'),
    ]),
    ('writing-task-1', 'Writing Task 1 | Course Enrolment', [
        ('Select the main features', ['All three classes grow', 'Class C stays the largest', 'Compare changes across years'], 'Begin by identifying the main features of the table. All three classes grew between twenty twenty two and twenty twenty four. Class C remained the largest.'),
        ('Make accurate comparisons', ['Class A: 20 to 35', 'Class B: 30 to 45', 'Class C: 40 to 50'], 'Class A and Class B each gained fifteen students. Class C increased by ten. Use these comparisons rather than describing every number separately.'),
        ('Review your response', ['Introduction and overview', 'Details with units and dates', 'Do not invent causes'], 'Organize your response with an introduction, an overview, and supporting details. Check the dates and units. The table does not explain why enrolment increased, so do not invent causes.'),
    ]),
    ('writing-task-2', 'Writing Task 2 | Online Learning', [
        ('State your position', ['Should online learning replace classrooms?', 'Choose a clear position', 'Keep it consistent'], 'The question asks whether online learning should replace classroom learning. State your position clearly. One possible position is that online lessons should complement classroom teaching.'),
        ('Develop your argument', ['Point, explanation, example', 'Flexibility for working students', 'Direct discussion in classrooms'], 'Develop each main idea with an explanation and a relevant example. Recorded lessons offer flexibility for workers. Classroom discussion can support interaction and group activities.'),
        ('Check the conclusion', ['Answer the original question', 'Summarize the main reasons', 'Avoid unsupported statistics'], 'Your conclusion should answer the original question and remain consistent with your introduction. Summarize the main reasons. Avoid invented statistics or studies.'),
    ]),
    ('speaking', 'Speaking | A Skill You Learned', [
        ('Plan a personal example', ['What was the skill?', 'How did you learn it?', 'What was difficult?'], 'Choose a skill you learned and organize a personal example. Explain what the skill was, how you learned it, and what you found difficult.'),
        ('Add concrete details', ['Cooking simple meals', 'Practising at weekends', 'Managing the timing'], 'For example, you could describe learning to cook simple meals. Explain that you practised at weekends and found it difficult to time several ingredients.'),
        ('Extend the discussion', ['Give a reason and an example', 'Compare individual and group learning', 'Practise speaking, not memorizing'], 'Extend your answers with reasons and examples. Individual practice is flexible, while group learning can provide feedback. This video uses a synthetic voice and does not assess your pronunciation.'),
    ]),
]


def run(args):
    subprocess.run(args, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)


def duration(path):
    return float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', str(path)]))


def speech(text, path, voice='en-gb', pitch=48):
    source = path.with_suffix('.txt')
    source.write_text(text)
    run(['espeak-ng', '-v', voice, '-s', '145', '-p', str(pitch), '-f', str(source), '-w', str(path)])


def stamp(seconds):
    ms = round(seconds * 1000)
    return f'{ms // 3600000:02}:{ms // 60000 % 60:02}:{ms // 1000 % 60:02}.{ms % 1000:03}'


def slide(title, heading, bullets, index, path):
    img = Image.new('RGB', (1280, 720), '#10172b')
    draw = ImageDraw.Draw(img)
    draw.rounded_rectangle((52, 45, 1228, 675), 28, fill='#18233e', outline='#435379', width=2)
    draw.text((88, 78), 'ENGLISHUP  /  IELTS 6.5 PRACTICE', font=ImageFont.truetype(FONT, 22), fill='#a79aff')
    draw.text((88, 134), title, font=ImageFont.truetype(FONT, 31), fill='#ffffff')
    draw.line((88, 199, 1192, 199), fill='#435379', width=2)
    draw.text((88, 238), heading, font=ImageFont.truetype(FONT, 38), fill='#c4b7ff')
    y = 330
    for bullet in bullets:
        draw.ellipse((94, y + 12, 106, y + 24), fill='#38d2bd')
        for line in textwrap.wrap(bullet, 55):
            draw.text((130, y), line, font=ImageFont.truetype(FONT, 29), fill='#e2e8f0')
            y += 45
        y += 18
    draw.text((88, 625), 'Original demo material  |  Synthetic voice  |  Not an official IELTS test', font=ImageFont.truetype(FONT, 18), fill='#9caecc')
    draw.text((1150, 78), f'{index}/3', font=ImageFont.truetype(FONT, 22), fill='#9caecc')
    img.save(path)


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='ielts-media-') as temporary:
        tmp = Path(temporary)
        # Separate voices for the two speakers; the text exactly matches the lesson transcript.
        dialogue = [
            ('Receptionist', 'Good morning. How can I help you?'),
            ('Student', 'I would like to join the evening English course. My name is Maya Chen.'),
            ('Receptionist', 'The class normally starts at six, but next Tuesday it will begin at half past six.'),
            ('Student', 'Which room should I go to?'),
            ('Receptionist', 'Room twelve, on the second floor. Please bring a notebook. The course fee is eighty pounds.'),
            ('Student', 'Can I pay on the first day?'),
            ('Receptionist', 'Yes, but please register by Friday.'),
        ]
        audio_parts = []
        transcript = []
        for i, (speaker, text) in enumerate(dialogue):
            wav = tmp / f'dialogue-{i}.wav'
            speech(text, wav, 'en-gb' if speaker == 'Receptionist' else 'en-us', 42 if speaker == 'Receptionist' else 60)
            audio_parts.append(wav)
            transcript.append(f'{speaker}: {text}')
        concat = tmp / 'audio.txt'
        concat.write_text(''.join(f"file '{part}'\n" for part in audio_parts))
        run(['ffmpeg', '-y', '-f', 'concat', '-safe', '0', '-i', str(concat), '-c:a', 'libmp3lame', '-b:a', '128k', str(OUT / 'listening-registration.mp3')])
        (OUT / 'listening-transcript.txt').write_text('\n'.join(transcript)+'\n')
        model = 'A skill I learned recently is cooking simple meals. I watched short tutorials and practised at weekends. At first, managing the timing was difficult. For example, I sometimes finished the vegetables before the rice was ready. With practice, I became more organized. This skill is useful because I can prepare healthier lunches. Learning alone gives me flexibility, while learning with other people can provide feedback.'
        speech(model, tmp / 'speaking.wav')
        run(['ffmpeg', '-y', '-i', str(tmp / 'speaking.wav'), '-c:a', 'libmp3lame', '-b:a', '128k', str(OUT / 'speaking-model.mp3')])
        (OUT / 'speaking-model.txt').write_text(model+'\n')
        for unit_index, (key, title, sections) in enumerate(SLIDES):
            segments = []
            subtitles = ['WEBVTT\n\n']
            elapsed = 0
            for j, (heading, bullets, narration) in enumerate(sections):
                png, wav, video = tmp / f'{key}-{j}.png', tmp / f'{key}-{j}.wav', tmp / f'{key}-{j}.mp4'
                slide(title, heading, bullets, j + 1, png)
                if j == 0: img = Image.open(png); img.save(OUT / f'{key}-poster.jpg', quality=90)
                speech(narration, wav)
                length = duration(wav)
                run(['ffmpeg', '-y', '-loop', '1', '-framerate', '12', '-i', str(png), '-i', str(wav), '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '24', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '96k', '-t', str(length), '-movflags', '+faststart', str(video)])
                segments.append(video)
                subtitles.append(f'{stamp(elapsed)} --> {stamp(elapsed + length)}\n'+ '\n'.join(textwrap.wrap(narration, 80))+'\n\n')
                elapsed += length
            concat.write_text(''.join(f"file '{part}'\n" for part in segments))
            run(['ffmpeg', '-y', '-f', 'concat', '-safe', '0', '-i', str(concat), '-c', 'copy', '-movflags', '+faststart', str(OUT / f'{key}-guide.mp4')])
            (OUT / f'{key}-guide.vtt').write_text(''.join(subtitles))
            pages = []
            sheet = Image.new('RGB', (1240, 1754), 'white')
            pen = ImageDraw.Draw(sheet)
            y = 85
            text = title+'\nOriginal practice worksheet / IELTS 6.5 demo\n\n'+UNITS[unit_index]['body']+'\n\n'+UNITS[unit_index]['assignment']
            for paragraph in text.splitlines():
                for line in textwrap.wrap(paragraph, 85) or ['']:
                    if y > 1640:
                        pages.append(sheet)
                        sheet = Image.new('RGB', (1240, 1754), 'white')
                        pen = ImageDraw.Draw(sheet)
                        y = 85
                    pen.text((80, y), line, font=ImageFont.truetype(FONT, 23), fill='#18233e')
                    y += 34
            pages.append(sheet)
            pages[0].save(OUT / f'{key}-worksheet.pdf', 'PDF', resolution=150, save_all=True, append_images=pages[1:])
            print(f'Built {key}: {elapsed:.1f}s video', flush=True)
    files = {}
    for path in sorted(OUT.iterdir()):
        if path.suffix not in ['.mp3', '.mp4', '.pdf', '.vtt', '.txt', '.jpg']: continue
        item = {'bytes': path.stat().st_size, 'sha256': hashlib.sha256(path.read_bytes()).hexdigest()}
        if path.suffix in ['.mp3', '.mp4']: item['duration_seconds'] = round(duration(path), 2)
        files[path.name] = item
    (OUT / 'manifest.json').write_text(json.dumps({'version': 'IELTS_65_MEDIA_V1', 'origin': 'Original local demo; espeak-ng synthetic voices; slide videos; not official IELTS material.', 'files': files}, ensure_ascii=False, indent=2)+'\n')
    print(f'Created {len(files)} assets in {OUT}', flush=True)


if __name__ == '__main__':
    main()
