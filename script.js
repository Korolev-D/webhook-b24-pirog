$(document).ready(function() {
    request();
    var interval_id = setInterval(function() {
        console.log('Прошла одна минута');
        request();
    }, 1000 * 60);

    function request() {
        $.ajax({
            url: 'handler.php',
            method: 'post',
            dataType: "json",
            success: function(data) {
                console.log(data)
                $('tbody').html(data.table)
                if (data.sound) {
                    $('.sound').append("<div id=\"sound_track\"><audio autoplay><source src=\"audio.mp3\"></audio></div>");
                }
            }
        });
    }
});