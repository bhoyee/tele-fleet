<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class UserManualController extends Controller
{
    public function __invoke(): View
    {
        $path = base_path('docs/user-manual.md');
        $markdown = File::exists($path)
            ? File::get($path)
            : "# User Manual\n\nThe user manual file was not found.";

        $html = Str::markdown($markdown);
        $safeHtml = $this->sanitizeHtml($html);

        return view('admin.user-manual', [
            'content' => $safeHtml,
        ]);
    }

    private function sanitizeHtml(string $html): string
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $this->ensureHeadingIds($dom);

        foreach (['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach ($xpath->query('//*[@*]') as $node) {
            if (! $node->hasAttributes()) {
                continue;
            }
            $remove = [];
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim($attr->value);

                if (str_starts_with($name, 'on')) {
                    $remove[] = $attr->name;
                    continue;
                }

                if (in_array($name, ['href', 'src'], true)) {
                    $lowerValue = strtolower($value);
                    $isAllowed = str_starts_with($lowerValue, 'http://')
                        || str_starts_with($lowerValue, 'https://')
                        || str_starts_with($lowerValue, '/')
                        || str_starts_with($lowerValue, '#');
                    if (! $isAllowed) {
                        $remove[] = $attr->name;
                    }
                }
            }
            foreach ($remove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        return $dom->saveHTML();
    }

    private function ensureHeadingIds(DOMDocument $dom): void
    {
        $used = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                if ($node->hasAttribute('id')) {
                    $used[$node->getAttribute('id')] = true;
                    continue;
                }
                $text = trim($node->textContent ?? '');
                if ($text === '') {
                    continue;
                }
                $base = Str::slug($text);
                if ($base === '') {
                    continue;
                }
                $id = $base;
                $suffix = 2;
                while (isset($used[$id])) {
                    $id = $base . '-' . $suffix;
                    $suffix++;
                }
                $node->setAttribute('id', $id);
                $used[$id] = true;
            }
        }
    }
}
