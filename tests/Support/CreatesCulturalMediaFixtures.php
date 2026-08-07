<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Binarni fixture-i za TS-008 testove (bez GD ekstenzije).
 */
trait CreatesCulturalMediaFixtures
{
    protected function fixtureJpeg1x1(): string
    {
        return (string) base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAACfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8hf//Z',
            true
        );
    }

    protected function fixturePng1x1(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
    }

    protected function fixtureWebp1x1(): string
    {
        return (string) base64_decode(
            'UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=',
            true
        );
    }

    protected function uploadJpeg(string $name = 'cover.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->fixtureJpeg1x1());
    }

    protected function uploadPng(string $name = 'cover.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->fixturePng1x1());
    }

    protected function uploadWebp(string $name = 'cover.webp'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->fixtureWebp1x1());
    }
}
