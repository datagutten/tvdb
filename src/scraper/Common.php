<?php

namespace datagutten\tvdb\scraper;


use DOMXPath;
use datagutten\tvdb\exceptions;

class Common
{
    /**
     * Get overview languages
     * @param DOMXPath $xpath
     * @return array
     */
    public static function languages(DOMXPath $xpath): array
    {
        $languages_dom = $xpath->query('//span[@data-language]');
        //$languages_dom = $xpath->query("//div[@id='translations']/div");
        $languages = [];
        foreach ($languages_dom as $language) {
            $id = $language->getAttribute('data-language');
            $languages[$id] = $language->textContent;
        }
        return $languages;
    }

    public static function default_language(DOMXPath $xpath): string
    {
        $language = $xpath->query('//span[@class="label label-info change_translation"]/@data-language');
        return $language->item(0)->nodeValue;
    }

    /**
     * Get series title from breadcrumbs
     * @param $xpath
     * @return string
     */
    public static function title_crumbs(DOMXPath $xpath): string
    {
        $title = $xpath->query('//div[@class="crumbs"]/a[starts-with(@href, "/series/")]');
        return trim($title->item(0)->textContent);
    }

    public static function translation(DOMXPath $xpath, string $language): array
    {
        $translation = $xpath->query(sprintf("//div[@id='translations']/div[@data-language=\"%s\"]", $language));
        if ($translation->length == 0)
            throw new exceptions\TranslationNotFound(sprintf('Translation for %s not found', $language));
        $translation = $translation->item(0);
        if (empty($translation))
            return [null, null];
        $title = $translation->attributes->getNamedItem('data-title')->textContent;
        $overview = $translation->childNodes->item(1)->textContent;
        return [$title, $overview];
    }
}