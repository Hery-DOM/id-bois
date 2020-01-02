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

//close modal with carousel
function modalClose(){
    $('.project-carousel-close').click(function(){
        $('.gallery-category-modal-hidden').hide();
    });
}


//carousel for a project
function modalShow(id){
    $('.gallery-category-modal-hidden').css('display','block')
        $('.gallery-category-modal-hidden').load('../project/'+id,function(){
            modalClose();
    });
}

//show hidden menu
function showHiddenMenu(){
    $('.base-hidden-menu').css('left',0);
}

//close hidden menu
function closeHiddenMenu(){
    $('.base-hidden-menu').css('left','-100vw');
}

//AJAX pour les cookies
$('.cookie-btn').click(function(){
    var test = $.ajax({
        url: 'cookie',
        type: 'POST',
        dataType: 'html',
        complete: function(){
            $('.cookie').css('display','none');
        }
    });
    console.log(test);
});


// ADMIN confirm remove (project in gallery or article in ecolo)
function confirmRemove(url){
    if(confirm('Confirmer la suppresion ?')){
        $(location).attr('href',url);
    }
}