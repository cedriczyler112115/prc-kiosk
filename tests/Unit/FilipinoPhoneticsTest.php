<?php

namespace Tests\Unit;

use App\Services\FilipinoPhonetics;
use PHPUnit\Framework\TestCase;

class FilipinoPhoneticsTest extends TestCase
{
    public function test_basic_dictionary_mappings_tagalog()
    {
        $svc = new FilipinoPhonetics;
        $out = $svc->transform('Filipino bayan', 'tagalog');
        $this->assertStringContainsString('Fi-li-pí-no', $out);
        $this->assertStringContainsString('bá-yan', $out);
    }

    public function test_syllabify_when_looks_filipino()
    {
        $svc = new FilipinoPhonetics;
        $out = $svc->transform('mga tao', 'tagalog');
        $this->assertStringContainsString('ma-nga', $out);
    }

    public function test_cebuano_and_ilocano_dictionaries()
    {
        $svc = new FilipinoPhonetics;
        $ceb = $svc->transform('maayong salamat', 'cebuano');
        $ilo = $svc->transform('agyamanak naimbag apu', 'ilocano');
        $this->assertStringContainsString('ma-á-yong', $ceb);
        $this->assertStringContainsString('sa-lá-mat', $ceb);
        $this->assertStringContainsString('a-gya-ma-nak', $ilo);
        $this->assertStringContainsString('na-im-bág', $ilo);
        $this->assertStringContainsString('a-pú', $ilo);
    }

    public function test_performance_under_threshold()
    {
        $svc = new FilipinoPhonetics;
        $text = str_repeat('mga mamamayan ng pilipinas ', 50);
        $start = microtime(true);
        $out = $svc->transform($text, 'tagalog');
        $elapsedMs = (microtime(true) - $start) * 1000;
        $this->assertLessThan(200, $elapsedMs, 'Transform should be under 200ms');
        $this->assertNotEmpty($out);
    }
}
