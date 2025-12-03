<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

trait LocaleHelper
{
    protected function setLocaleFromRequest(): void
    {
        $locale = $this->determineLocale();
        App::setLocale($locale);

        if (! request()->is('api/*')) {
            session(['locale' => $locale]);
        }
    }

    private function determineLocale(): string
    {
        $supportedLocales = ['tr', 'en'];
        $defaultLocale = config('app.locale', 'tr');

        if (request()->is('api/*')) {
            $queryLocale = request()->query('locale');
            if ($queryLocale && in_array($queryLocale, $supportedLocales)) {
                return $queryLocale;
            }

            $acceptLanguage = request()->header('Accept-Language');
            if ($acceptLanguage) {
                $locale = substr($acceptLanguage, 0, 2);
                if (in_array($locale, $supportedLocales)) {
                    return $locale;
                }
            }

            return $defaultLocale;
        }

        $urlSegment = request()->segment(1);
        if (in_array($urlSegment, $supportedLocales)) {
            return $urlSegment;
        }

        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, $supportedLocales)) {
            return $sessionLocale;
        }

        return $defaultLocale;
    }

    protected function formatDateByCurrentLocale(string $date): string
    {
        $locale = App::getLocale();
        $format = $locale === 'tr' ? 'd/m/Y' : 'm/d/Y';

        return Carbon::parse($date)->format($format);
    }

    protected function formatDateTimeByCurrentLocale(string $dateTime): string
    {
        $locale = App::getLocale();
        $format = $locale === 'tr' ? 'd/m/Y H:i' : 'm/d/Y H:i';

        return Carbon::parse($dateTime)->format($format);
    }
}
