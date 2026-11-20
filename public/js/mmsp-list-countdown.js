(function (root) {
    function unit(count, one, few, many) {
        var mod10 = count % 10;
        var mod100 = count % 100;

        if (mod10 === 1 && mod100 !== 11) {
            return one;
        }

        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
            return few;
        }

        return many;
    }

    function format(distanceMs) {
        if (distanceMs < 0) {
            return 'Rok je istekao';
        }

        var days = Math.floor(distanceMs / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distanceMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distanceMs % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distanceMs % (1000 * 60)) / 1000);
        var text = '';

        if (days > 0) {
            text += days + ' ' + unit(days, 'dan', 'dana', 'dana');
            if (hours > 0 || minutes > 0) {
                text += ', ';
            }
        }

        if (hours > 0) {
            text += hours + ' ' + unit(hours, 'sat', 'sata', 'sati');
            if (minutes > 0) {
                text += ', ';
            }
        }

        if (minutes > 0) {
            text += minutes + ' ' + unit(minutes, 'minut', 'minuta', 'minuta');
        }

        if (days === 0 && hours === 0 && minutes === 0) {
            text = seconds + ' ' + unit(seconds, 'sekund', 'sekunda', 'sekundi');
        }

        return text;
    }

    root.MmspListCountdown = {
        unit: unit,
        format: format,
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
