import { Fancybox } from "@fancyapps/ui";
import { Carousel } from "@fancyapps/ui/dist/carousel/carousel.esm.js";
import { Autoplay } from "@fancyapps/ui/dist/carousel/carousel.autoplay.esm.js";


import { Dropzone } from "dropzone";

// fancybox
Fancybox.bind("[data-fancybox]");
  

// События
addEventListener('ajax:done', function(event) {
    let handler = event.detail.context.handler;
    

    // onModalOpen
    if(handler == 'onModalOpen'){
        modal(event.detail.data);
    }

});

// dropzone
let dropzones = document.querySelectorAll('.izones');

if(dropzones.length){
    dropzones.forEach(el => {

        let id = el.getAttribute('data-id');  
        let formId = el.getAttribute('data-form');     

        new Dropzone(el, { 
            url: "/api/photo/add",
            dictDefaultMessage: el.getAttribute('data-placeholder'),  
            init: function(){
                this.on("sending", function(file, xhr, data) {
                    data.append("form", formId);
                });
            },
            success(file, data) {
                if (file.previewElement) {                    
                    addPhoto(data, id);
                    return file.previewElement.classList.add("dz-success");
                }
            },  
        });

    })
}

// Добавялем input в блок
window.addPhoto = function(file, id){
    let wrap = document.getElementById(id);

    let el = document.createElement("input");
    el.setAttribute('type', 'hidden');
    el.setAttribute('name', id + '[]');
    el.value = file.id;

    wrap.append(el);
    //console.log(file.id);
}

// Формат телефона
window.setPhone = function(phone){
    // if(phone.value){
    //     let text = phone.value.substring(1);
    //     text = "7" + text;
    //     phone.value = text;
    // }    
}

// Модальное окно
function modal(data){
    new Fancybox([
        {
            src: data.modal,
            type: "html",
        },
    ]);      
}


// Баннер главной
let swiperbnrs = document.getElementById('swiper-bnrs');
if(swiperbnrs){    
    new Carousel(swiperbnrs, { 
        infinite: false 
    });
}

// Слайды отзывы
let swiperreview = document.getElementById('swiper-review');
if(swiperreview){
    new Carousel(swiperreview, { 
        infinite: false 
    });
}

// Карусель гос номеров
let gosNumbersTop = document.getElementById('carousel-numbers');
if(gosNumbersTop){
    new Carousel(gosNumbersTop, { 
        infinite: true,
        Dots: false,
        Autoplay: {
            timeout: 50000,
        },
    }, { Autoplay });    
}

// Подгрузка динамических данныз формы
window.getWrapForm = function(data){

    // onGetAjaxFormWrap
    let wrap = data.getAttribute('data-wrap');
    let id = data.getAttribute('data-id');

    oc.ajax('onGetAjaxFormWrap', {
        data: {
            wrap: wrap,
            id: id
        },
        success: function(data) {
            this.success(data);
        }
    })
    
}