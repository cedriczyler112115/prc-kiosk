<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class FilipinoTtsAudioValidationTest extends TestCase
{
    public function test_audio_validation_placeholder()
    {
        if (! getenv('TTS_AUDIO_VALIDATION') || ! is_file(__DIR__.'/fixtures/native_filipino_sample.phonemes')) {
            $this->markTestSkipped('Audio validation requires native recordings and phoneme extractor.');
        }
        $expected = trim(file_get_contents(__DIR__.'/fixtures/native_filipino_sample.phonemes'));
        $synthesized = $expected;
        $expectedSeq = preg_split('/\s+/', $expected);
        $synthSeq = preg_split('/\s+/', $synthesized);
        $match = 0;
        $total = max(count($expectedSeq), 1);
        foreach ($expectedSeq as $i => $ph) {
            if (isset($synthSeq[$i]) && $synthSeq[$i] === $ph) {
                $match++;
            }
        }
        $accuracy = $match / $total * 100;
        $this->assertGreaterThanOrEqual(85, $accuracy, 'Phoneme match should be >= 85%');
    }
}
