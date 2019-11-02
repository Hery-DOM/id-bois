//profile : slide left/right
$(document).ready(function(){
    $('.profile').click(function(){
        $('.profile-content').css('display','block').animate({left:0},600,function(){
            $('.profile-content-close').delay(800).click(function(){
                $('.profile-content').animate({left:'100vw'},600,function(){
                    $('.profile-content').css('display','none');
                });
            });
        });
    });
});