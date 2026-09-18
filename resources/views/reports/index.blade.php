@extends('layouts.app')

@section('css')
@vite('resources/css/reports/index.css')
@endsection

@section('content')
<div class="report__content">
    <div class="content__header">
        <h1 class="content__header--item">マイ勤怠レポート</h1>
    </div>

    <div class="summary">
        <div class="summary__item">
            <p class="summary__label">総労働時間（過去6ヶ月）</p>
            <p class="summary__value">{{ $totalWorkTime }}</p>
        </div>
        <div class="summary__item">
            <p class="summary__label">総残業時間（過去6ヶ月）</p>
            <p class="summary__value">{{ $totalOvertime }}</p>
        </div>
        <div class="summary__item">
            <p class="summary__label">平均労働時間（1日あたり）</p>
            <p class="summary__value">{{ $averageWorkTime }}</p>
        </div>
    </div>

    <table class="table">
        <tr class="table__row">
            <th class="table__header">
                <p class="table__header--item">月</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">労働時間</p>
            </th>
        </tr>
        @foreach ($monthlyTrend as $month)
        <tr class="table__row">
            <td class="table__description">
                <p class="table__description--item">{{ $month['month'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $month['totalWorkTime'] }}</p>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="content__header">
        <h2 class="content__header--item">今月の異常検知</h2>
    </div>
    <div class="summary">
        <div class="summary__item">
            <p class="summary__label">遅刻</p>
            <p class="summary__value">{{ $lateCount }}回</p>
        </div>
        <div class="summary__item">
            <p class="summary__label">早退</p>
            <p class="summary__value">{{ $earlyLeaveCount }}回</p>
        </div>
        <div class="summary__item">
            <p class="summary__label">長時間労働</p>
            <p class="summary__value">{{ $longWorkCount }}日</p>
        </div>
    </div>
</div>
@endsection
