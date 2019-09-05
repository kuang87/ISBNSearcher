$(document).ready(function () {
    $('#search').click(function () {
        $('#isbn').empty();
        $.ajax({
            url: '/api.php?isbn',
            dataType: 'json',
        })
            .done(function (json) {
                $('#report').attr('value', json.report_id);
                console.log(json.report_id);
                $.each(json.report_data, function (index, msg) {
                    $('#isbn').append('<table class="table table-bordered"><tr>' + '<td>' + msg.id_report + '</td>' +
                        '<td>' + msg.message + '</td>' +
                        '<td>' + msg.id_book + '</td>' +
                        '<td>' + msg.description_ru + '</td>' +
                        '</tr></table>');
                });

            })
    });

    $('#csv').click(function () {
        $.ajax({
            url: '/api.php',
            dataType: 'json',
            data: {
                report: $('#report').val(),
            },
        })
            .done(function (json) {
                $('#report').attr('value', json);
                console.log(json);
            })
    });

});