<?php

namespace App\Services;

class FilipinoPhonetics
{
    protected array $dict = [
        'tagalog' => [
            'mga' => 'ma-nga',
            'ng' => 'ng',
            'pasensiya' => 'pa-sén-sya',
            'quezon' => 'ké-zon',
            'bayan' => 'bá-yan',
            'pangulo' => 'pa-gú-lo',
            'filipino' => 'fi-li-pí-no',
            'pilipino' => 'pi-li-pí-no',
        ],
        'cebuano' => [
            'maayong' => 'ma-á-yong',
            'salamat' => 'sa-lá-mat',
            'daghang' => 'dag-hang',
        ],
        'ilocano' => [
            'agyamanak' => 'a-gya-ma-nak',
            'naimbag' => 'na-im-bág',
            'apu' => 'a-pú',
        ],
    ];

    public function transform(string $text, string $variant = 'tagalog'): string
    {
        $variant = strtolower($variant);
        if (! isset($this->dict[$variant])) {
            return $text;
        }
        $dict = $this->dict[$variant];
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        foreach ($tokens as $tok) {
            $base = mb_strtolower(trim($tok));
            if ($base === '') {
                $out[] = $tok;

                continue;
            }
            if (isset($dict[$base])) {
                $mapped = $dict[$base];
                $out[] = $this->preserveCase($tok, $mapped);

                continue;
            }
            if ($this->looksFilipino($base)) {
                $out[] = $this->syllabify($tok);
            } else {
                $out[] = $tok;
            }
        }

        return implode('', $out);
    }

    protected function preserveCase(string $orig, string $mapped): string
    {
        $first = mb_substr($orig, 0, 1);
        if (mb_strtoupper($first) === $first) {
            return mb_strtoupper(mb_substr($mapped, 0, 1)).mb_substr($mapped, 1);
        }

        return $mapped;
    }

    protected function looksFilipino(string $token): bool
    {
        if (preg_match('/[áéíóúñ]/iu', $token)) {
            return true;
        }
        if (preg_match('/\b(ng|mga|si|ang|sa|kay|mag|nag|pag)\b/u', $token)) {
            return true;
        }

        return false;
    }

    protected function syllabify(string $word): string
    {
        $w = mb_strtolower($word);
        $vowels = 'aeiouáéíóú';
        $len = mb_strlen($w);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($w, $i, 1);
            $out .= $ch;
            $next = $i + 1 < $len ? mb_substr($w, $i + 1, 1) : '';
            $next2 = $i + 2 < $len ? mb_substr($w, $i + 2, 1) : '';
            if (mb_strpos($vowels, $ch) !== false && $next && mb_strpos($vowels, $next) === false && $next2) {
                if (mb_strpos($vowels, $next2) !== false) {
                    $out .= '-';
                }
            }
        }

        return $out;
    }
}
