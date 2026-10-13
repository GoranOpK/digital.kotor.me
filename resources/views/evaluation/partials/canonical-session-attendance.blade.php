<fieldset style="margin: 8px 0;">
    <legend>Prisustvo trenutnih članova</legend>
    @foreach($seats as $seatNo => $member)
        @if($member)
            @php $selected = $current->get($member->id); @endphp
            <label style="display: block;">
                Mjesto {{ $seatNo }} — {{ $member->name }}
                <select name="attendance[{{ $member->id }}]">
                    <option value="absent">Odsutan</option>
                    @foreach($attendanceModes as $mode => $label)
                        <option value="{{ $mode }}" @selected($selected?->present && $selected->attendance_mode === $mode)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <p>Mjesto {{ $seatNo }}: upražnjeno — prisustvo se ne može evidentirati.</p>
        @endif
    @endforeach
</fieldset>
