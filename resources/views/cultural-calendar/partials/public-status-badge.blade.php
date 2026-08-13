{{--
  CR-004A / PO-CR4A-01…05 / PO-6A11-01 — zajednički javni status badge.
  @var \App\Models\CulturalEventEntry $event
  @var string $variant  card|detail
--}}
@php
    $variant = $variant ?? 'card';
    $status = $event->publicStatus();
@endphp

@once
    <style>
        .kk-public-status-photo {
            position: relative;
        }
        .kk-public-status-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.01em;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid transparent;
            max-width: calc(100% - 16px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-sizing: border-box;
        }
        .kk-public-status-badge--card {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;
        }
        .kk-public-status-badge--detail {
            margin: 0 0 0.75rem;
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        .kk-status-upcoming {
            color: #1e3a5f;
            background: #e8f1fb;
            border-color: #b6d0ef;
        }
        .kk-status-ongoing {
            color: #14532d;
            background: #e8f8ee;
            border-color: #a7e0b8;
        }
        .kk-status-finished {
            color: #374151;
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .kk-status-cancelled {
            color: #7f1d1d;
            background: #fde8e8;
            border-color: #f5b5b5;
        }
    </style>
@endonce

@if($status)
    <span class="kk-public-status-badge kk-public-status-badge--{{ $variant }} {{ $status['class'] }}">{{ $status['label'] }}</span>
@endif
