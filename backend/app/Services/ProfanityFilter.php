<?php

namespace App\Services;

class ProfanityFilter
{
    /**
     * Lijst van scheldwoorden en ongepaste woorden (in het Nederlands)
     * In een productie omgeving zou dit uit een database of configuratiebestand komen
     */
    protected $profanityList = [
        // Scheldwoorden (basis set)
        'kut', 'klootzak', 'flikker', 'homo', 'neger', 'nigger', 'trut', 'bitch', 'fuck', 'shit',
        'piss', 'damn', 'hell', 'bastard', 'asshole', 'motherfucker', 'cunt', 'dickhead',
        'fucker', 'prick', 'whore', 'slut', 'hoer', 'teef',
        
        // Seksuele termen (18+)
        'porno', 'porn', 'xxx', 'sex', 'seks', 'naakt', 'nude', 'pornografie', 'pornography',
        'verkrachting', 'rape', 'incest', 'pedofilie', 'pedofiel', 'pedophile',
        'explicit', 'expliciet', 'erotisch', 'erotic',
        
        // Geweld termen
        'moord', 'murder', 'geweld', 'violence', 'terrorisme', 'terrorism', 'bom', 'bomb',
        'wapen', 'weapon', 'moorden', 'kill',
        
        // Drugs termen
        'cocaine', 'heroin', 'drugs', 'drug', 'coke', 'marijuana', 'cannabis', 'wiet',
        'hasj', 'hash', 'xtc', 'mdma',
    ];

    /**
     * Check of tekst profanity bevat
     *
     * @param string $text
     * @return bool
     */
    public function containsProfanity(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');
        
        // Split text into words (behouden leestekens voor context)
        $words = preg_split('/[\s]+/u', $text);
        
        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word) || mb_strlen($word) < 3) {
                continue;
            }
            
            // Remove punctuation for matching
            $cleanWord = preg_replace('/[^\p{L}0-9]/u', '', $word);
            if (mb_strlen($cleanWord) < 3) {
                continue;
            }
            
            // Check exact matches first
            if (in_array($cleanWord, $this->profanityList)) {
                return true;
            }
            
            // Check if word contains profanity (partial match, maar alleen als het woord niet te lang is)
            foreach ($this->profanityList as $profanity) {
                if (mb_strlen($profanity) >= 3 && 
                    (strpos($cleanWord, $profanity) !== false || strpos($profanity, $cleanWord) !== false)) {
                    // Extra check: alleen als het een significant deel is van het woord
                    $similarity = similar_text($cleanWord, $profanity);
                    if ($similarity / max(mb_strlen($cleanWord), mb_strlen($profanity)) > 0.6) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Filter profanity uit tekst
     *
     * @param string $text
     * @param string $replacement
     * @return string
     */
    public function filterProfanity(string $text, string $replacement = '***'): string
    {
        $words = preg_split('/[\s\p{P}]+/u', $text);
        $filtered = [];
        
        foreach ($words as $word) {
            $originalWord = $word;
            $word = mb_strtolower(trim($word), 'UTF-8');
            
            $isProfanity = false;
            foreach ($this->profanityList as $profanity) {
                if ($word === $profanity || strpos($word, $profanity) !== false) {
                    $isProfanity = true;
                    break;
                }
            }
            
            $filtered[] = $isProfanity ? $replacement : $originalWord;
        }
        
        return implode(' ', $filtered);
    }
}

