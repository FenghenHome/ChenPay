var myTimer;
function timer(intDiff) {
    var i = 0;
    myTimer = window.setInterval(function () {
        i++;
        var day = 0,
            hour = 0,
            minute = 0,
            second = 0;//时间默认值
        if (intDiff > 0) {
            day = Math.floor(intDiff / (60 * 60 * 24));
            hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
            minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
            second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
        }
        if (minute <= 9) minute = '0' + minute;
        if (second <= 9) second = '0' + second;
        $('#hour_show').html('<s id="h"></s>' + hour + '时');
        $('#minute_show').html('<s></s>' + minute + '分');
        $('#second_show').html('<s></s>' + second + '秒');
        if (hour <= 0 && minute <= 0 && second <= 0) {
            qrcode_timeout()
            clearInterval(myTimer);

        }
        intDiff--;
    }, 1000);
}


try {
    document.ontouchstart = function () {
        $('#use').hide();
    }
} catch (e) {

}
qrcode_timeout = function () { //二维码超时则停止显示二维码
    $("#show_qrcode").attr("src", '');
    $("#show_qrcode").attr("alt", '二维码失效');

    $("#msg h1").html("支付超时 请重新提交订单"); //过期提醒信息
}
show_Qrcode = function (data) {
    if (!data)return;
    if (data.qrcode)$("#show_qrcode").attr("src", data.qrcode);
    if (data.money) $("#money").html('￥' + data.money);
    var tps='到帐可能会有几分钟延迟 <br><span style="color:red">为了您及时到账 请务必付款'+data.money+'元并在备注注明"'+data.remarks+'"</span><br>';
    $("#msg h1").html(tps);
    show_desc(data);
}
function getDescMode(key, value) {
    var reslut = value ? '<dt>' + key + '</dt><dd>' + value + '</dd>' : '';
    return reslut;
}
show_desc = function (data) { //商品描述
    var html = '';
    html += getDescMode('金额', "￥" + data.money);
    //html += getDescMode('云端单号', data.order_id);
    html += getDescMode('创建时间', data.make_time);
    html += getDescMode('过期时间', data.end_time);
    $("#desc").html(html);
}
$(document).ready(function(){
    $(function () {
        timer(user_data.outTime || 360);
    });

    $('#orderDetail .arrow').click(function (event) {
        if ($('#orderDetail').hasClass('detail-open')) {
            $('#orderDetail .detail-ct').slideUp(500, function () {
                $('#orderDetail').removeClass('detail-open');
            });
        } else {
            $('#orderDetail .detail-ct').slideDown(500, function () {
                $('#orderDetail').addClass('detail-open');
            });
        }
    });
});

