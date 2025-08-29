import { Fancybox } from "@fancyapps/ui/dist/fancybox/";
import { Carousel } from "@fancyapps/ui/dist/carousel/";
import { Autoplay } from "@fancyapps/ui/dist/carousel/carousel.autoplay.js";
import { Autoscroll } from "@fancyapps/ui/dist/carousel/carousel.autoscroll.js";

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

    // onStatusApp
    if(handler == 'onStatusApp'){
        alert(1);
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
    Fancybox.show([
        {
            html: data.modal
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
    const options = {
        infinite: false 
    };
    Carousel(swiperreview, options, { Autoscroll }).init();  
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

// Калькулятор формы регистрации транспорта
window.calcSumForm = function(){

    let form = document.getElementById('formApp');  
    if(form == null) return;
    
    let items = form.querySelectorAll('[data-code="gde"]');

    if(!items.length) return;

    let rows = [];
    
    items.forEach(el => {
        if(el.checked){
            let gde_price = parseInt(el.getAttribute('data-price'));
            if(gde_price)
                rows['gde'] = {'price' : gde_price, 'name' : el.getAttribute('data-title-price')};  

            let transports = form.querySelectorAll('[data-code="transport"]');
            transports.forEach(tr => {
                if(tr.checked){
                    let transport_price = parseInt(tr.getAttribute('data-price'));
                    rows['transport'] = {'price' : transport_price, 'name' : tr.getAttribute('data-title-price')};             
                }   
            })    

        }        
    })  

    console.log(rows);

    // Калькуляция
    oc.ajax('onCalcFormSum', {
        data: rows,
        success: function(data) {
            this.success(data);
        }
    })

}

document.gosCalc = function(data){    

    let formInput = document.getElementById('formPrices');
    let formPrice = document.getElementById('formPrice');
    let checkGos = document.getElementById('check_gos');    

    let rows = [];
    let ptsrow = null;
    let prices = 0;    
    let totalprices = 0; 
    let transport = document.querySelector('input[data-name="transport"]:checked');
    let gde = document.querySelector('input[data-name="gde"]:checked');
    let certificate = document.querySelector('[data-prices="4"]');
    let commission = document.querySelector('[data-prices="8"]');

    if(transport == null || gde == null) return;   

    document.querySelectorAll('[data-prices]').forEach(row => {        
        row.classList.remove('row-price-active');
    })
    
    // Где
    if(transport.value == 5){
        // Автотранспорт            
        if(gde.value == 145 || gde.value == 27){
            let pricerow = document.querySelector('[data-prices="1"]');
            prices = prices + parseInt(pricerow.getAttribute('data-price'));
            pricerow.classList.add('row-price-active');
            rows.push(pricerow.getAttribute('data-title'));                
        }           
    } else if(transport.value == 6){
        // Мототранспорт           
        if(gde.value == 145 || gde.value == 27){
            let pricerow = document.querySelector('[data-prices="2"]');
            prices = prices + parseInt(pricerow.getAttribute('data-price'));
            pricerow.classList.add('row-price-active');
            rows.push(pricerow.getAttribute('data-title'));                
        }
    } else if(transport.value == 7){
        // Прицеп
        if(gde.value == 145 || gde.value == 27){
            let pricerow = document.querySelector('[data-prices="2"]');
            prices = prices + parseInt(pricerow.getAttribute('data-price'));
            pricerow.classList.add('row-price-active');
            rows.push(pricerow.getAttribute('data-title'));                
        }
    }

    rows.push(certificate.getAttribute('data-title')); // госпошлина за выдачу свидетельства о регистрации ТС 500 рублей
    prices = prices + parseInt(certificate.getAttribute('data-price'));
    certificate.classList.add('row-price-active');

    // Доп услуги  
    rows.push(commission.getAttribute('data-title')); // 300 руб. комиссия
    prices = prices;  
    totalprices = prices + parseInt(commission.getAttribute('data-price')); 
    commission.classList.add('row-price-active');    

    let wrap = document.getElementById('resultSum');
    wrap.innerHTML = '';
    wrap.className = 'border border-blue-300 rounded-md px-4 py-4 bg-blue-50';
    
    const h3 = document.createElement("h3");
    h3.className = 'text-lg/none mb-2 text-blue-1 underline font-bold';
    h3.innerHTML = 'Стоимость услуги';

    wrap.append(h3);    

    for(let row in rows){
        if(checkGos.checked == false){
            let srv = document.createElement("div");
            srv.innerHTML = rows[row];       
            wrap.append(srv); 
        }            
    }   

    formInput.value = JSON.stringify(rows);
    formPrice.value = prices;
    

    if(checkGos.checked == false){        

        // Гос пошлина оплачена
        const totalDiv = document.createElement("div");
        totalDiv.innerHTML = 'Госпошлина: ' + '<b class="text-red-1">' + prices +'</b>'+ ' руб.';
        totalDiv.className = 'mt-3 font-bold text-lg';
        wrap.append(totalDiv);

        const totalCommission = document.createElement("div");
        totalCommission.innerHTML = 'Комиссия: ' + '<b class="text-red-1">' + parseInt(commission.getAttribute('data-price')) +'</b>'+ ' руб.';
        totalCommission.className = 'font-bold text-lg';
        wrap.append(totalCommission);

    } else {

        document.querySelectorAll('[data-prices]').forEach(element => {
            element.classList.remove('row-price-active');
        })

        let comissData = document.querySelector('[data-prices="8"]');

        const comiss = document.createElement("div");
        comiss.innerHTML = comissData.getAttribute('data-title');  
        wrap.append(comiss); 

        totalprices = parseInt(commission.getAttribute('data-price'));
    }   
   

    const totalDivTotal = document.createElement("div");
    totalDivTotal.innerHTML = 'Итого: ' + '<b class="text-red-1">'+ totalprices +'</b>'+ ' руб.';
    totalDivTotal.className = 'font-bold text-base mob_l:text-lg text-pretty';
    wrap.append(totalDivTotal);

    
    
}


document.gosCalcTwo = function(data){ 

    let formInput = document.getElementById('formPrices');
    let formPrice = document.getElementById('formPrice');
    let checkGos = document.getElementById('check_gos'); 

    let ptsrow = null;
    let rows = [];
    let prices = 0;    
    let totalprices = 0; 
    let transport = document.querySelector('input[data-name="transport"]:checked');
    let gde = document.querySelector('input[data-name="gde"]:checked');
    let pts = document.querySelector('input[data-name="pts"]:checked');
    let certificate = document.querySelector('[data-prices="4"]');
    let commission = document.querySelector('[data-prices="8"]');

    if(transport == null || gde == null || pts == null) return;  
    
    document.querySelectorAll('[data-prices]').forEach(row => {
        row.classList.remove('row-price-active');
    })
    
    // Где
    if(gde.value == 27 || gde.value == 145){
        
        if(transport.value == 5){
            // Автотранспорт            
            if(gde.value == 145 || gde.value == 27){
                let pricerow = document.querySelector('[data-prices="1"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }           
        } else if(transport.value == 6){
            // Мототранспорт           
            if(gde.value == 145 || gde.value == 27){
                let pricerow = document.querySelector('[data-prices="2"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }
        } else if(transport.value == 7){
            // Прицеп
            if(gde.value == 145 || gde.value == 27){
                let pricerow = document.querySelector('[data-prices="2"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }
        }

    }    
    
    // ПТС
    if(pts.value == 152){
        // Внести изменения в текущий        
        ptsrow = document.querySelector('[data-prices="5"]');
        prices = prices + parseInt(ptsrow.getAttribute('data-price'));
        ptsrow.classList.add('row-price-active');
        rows.push(ptsrow.getAttribute('data-title'));
    } else if(pts.value == 153){
        // госпошлина за выдачу паспорта ТС 800 рублей
        ptsrow = document.querySelector('[data-prices="6"]');
        prices = prices + parseInt(ptsrow.getAttribute('data-price'));
        ptsrow.classList.add('row-price-active');
        rows.push(ptsrow.getAttribute('data-title'));
    } else if(pts.value == 154){
        // госпошлина за выдачу электронного паспорта ТС 0 рублей
        ptsrow = document.querySelector('[data-prices="7"]');
        prices = prices + parseInt(ptsrow.getAttribute('data-price'));
        ptsrow.classList.add('row-price-active');
        rows.push(ptsrow.getAttribute('data-title'));
    }

    rows.push(certificate.getAttribute('data-title')); // госпошлина за выдачу свидетельства о регистрации ТС 500 рублей
    prices = prices + parseInt(certificate.getAttribute('data-price'));
    certificate.classList.add('row-price-active');

    rows.push(commission.getAttribute('data-title')); // 300 руб. комиссия
    prices = prices;  
    totalprices = prices + parseInt(commission.getAttribute('data-price')); 
    commission.classList.add('row-price-active');       
    

    let wrap = document.getElementById('resultSum');
    wrap.innerHTML = '';
    wrap.className = 'border border-blue-300 rounded-md px-4 py-4 bg-blue-50';
    
    const h3 = document.createElement("h3");
    h3.className = 'text-lg/none mb-2 text-blue-1 underline font-bold';
    h3.innerHTML = 'Стоимость услуги';

    wrap.append(h3);    

    for(let row in rows){
        if(checkGos.checked == false){
            let srv = document.createElement("div");
            srv.innerHTML = rows[row];
            wrap.append(srv);   
        }  
    }   

    formInput.value = JSON.stringify(rows);
    formPrice.value = prices;

    totalprices = prices + parseInt(commission.getAttribute('data-price')); 

    if(checkGos.checked == false){

        // Гос пошлина оплачена
        const totalDiv = document.createElement("div");
        totalDiv.innerHTML = 'Госпошлина: ' + '<b class="text-red-1">' + prices +'</b>'+ ' руб.';
        totalDiv.className = 'mt-3 font-bold text-lg';
        wrap.append(totalDiv);

        const totalCommission = document.createElement("div");
        totalCommission.innerHTML = 'Комиссия: ' + '<b class="text-red-1">' + parseInt(commission.getAttribute('data-price')) +'</b>'+ ' руб.';
        totalCommission.className = 'font-bold text-lg';
        wrap.append(totalCommission);

    } else {
        
        document.querySelectorAll('[data-prices]').forEach(element => {
            element.classList.remove('row-price-active');
        })

        let comissData = document.querySelector('[data-prices="8"]');

        const comiss = document.createElement("div");
        comiss.innerHTML = comissData.getAttribute('data-title');  
        wrap.append(comiss); 

        totalprices = parseInt(commission.getAttribute('data-price'));

    }    

    const totalDivTotal = document.createElement("div");
    totalDivTotal.innerHTML = 'Итого: ' + '<b class="text-red-1">'+ totalprices +'</b>'+ ' руб.';
    totalDivTotal.className = 'font-bold text-base mob_l:text-lg text-pretty';
    wrap.append(totalDivTotal);
    
}

document.gosCalcThree = function(data){ 

    let formInput = document.getElementById('formPrices');
    let formPrice = document.getElementById('formPrice');
    let checkGos = document.getElementById('check_gos'); 

    let ptsrow = null;
    let rows = [];
    let prices = 0;    
    let totalprices = 0; 
    let transport = document.querySelector('input[data-name="transport"]:checked');
    let gde = document.querySelector('input[data-name="gde"]:checked');
    let pts = document.querySelector('input[data-name="pts"]:checked');
    let nomer = document.querySelector('input[data-name="nomer"]:checked');
    let certificate = document.querySelector('[data-prices="4"]');
    let commission = document.querySelector('[data-prices="8"]');

    if(transport == null || nomer == null) return; 

    document.querySelectorAll('[data-prices]').forEach(row => {
        row.classList.remove('row-price-active');
    })

    // Что сделать с номерами
    if(nomer){
        if(nomer.value != '161'){
            document.querySelectorAll('[data-name="gde"]').forEach(el => {
                el.setAttribute('disabled', true);
                el.checked = false;
            })        
        } else {
            document.querySelectorAll('[data-name="gde"]').forEach(el => {
                el.removeAttribute('disabled');
            })     
        }
    }   

    // Тип транспорта
    if(transport){        

        // Получить новые номера
        if(nomer.value == 161){
            if(gde == null) return; 
        }

        if(transport.value == 5 && nomer.value == 161){
            // Автотранспорт
            if(gde.value == 165){
                let pricerow = document.querySelector('[data-prices="1"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }           
        } else if(transport.value == 6 && nomer.value == 161){
            // Мототранспорт
            if(gde.value == 165){
                let pricerow = document.querySelector('[data-prices="2"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }
        } else if(transport.value == 7 && nomer.value == 161){
            // Прицеп
            if(gde.value == 165){
                let pricerow = document.querySelector('[data-prices="2"]');
                prices = prices + parseInt(pricerow.getAttribute('data-price'));
                pricerow.classList.add('row-price-active');
                rows.push(pricerow.getAttribute('data-title'));                
            }
        }
        
    }   

    if(pts){        
        if(pts.value == 152){
            // Внести изменения в текущий
            ptsrow = document.querySelector('[data-prices="5"]');
            prices = prices + parseInt(ptsrow.getAttribute('data-price'));
            ptsrow.classList.add('row-price-active');
            rows.push(ptsrow.getAttribute('data-title'));
        } else if(pts.value == 153){
            // Получить новый
            ptsrow = document.querySelector('[data-prices="6"]');
            prices = prices + parseInt(ptsrow.getAttribute('data-price'));
            ptsrow.classList.add('row-price-active');
            rows.push(ptsrow.getAttribute('data-title'));
        } else if(pts.value == 154){
            // У меня электронный паспорт
            ptsrow = document.querySelector('[data-prices="7"]');
            prices = prices + parseInt(ptsrow.getAttribute('data-price'));
            ptsrow.classList.add('row-price-active');
            rows.push(ptsrow.getAttribute('data-title'));    
        }
    }
    
    rows.push(certificate.getAttribute('data-title')); // госпошлина за выдачу свидетельства о регистрации ТС 500 рублей
    prices = prices + parseInt(certificate.getAttribute('data-price'));
    certificate.classList.add('row-price-active');

    rows.push(commission.getAttribute('data-title')); // 300 руб. комиссия
    prices = prices;  
    totalprices = prices + parseInt(commission.getAttribute('data-price')); 
    commission.classList.add('row-price-active'); 
  

    let wrap = document.getElementById('resultSum');
    wrap.innerHTML = '';
    wrap.className = 'border border-blue-300 rounded-md px-4 py-4 bg-blue-50';
    
    const h3 = document.createElement("h3");
    h3.className = 'text-lg/none mb-2 text-blue-1 underline font-bold';
    h3.innerHTML = 'Стоимость услуги';

    wrap.append(h3);    

    for(let row in rows){            
        if(checkGos.checked == false){
            let srv = document.createElement("div");
            srv.className = 'text-xs mob_l:text-base';
            srv.innerHTML = rows[row];
            wrap.append(srv);     
        }    
    }   

    formInput.value = JSON.stringify(rows);
    formPrice.value = prices;

    if(checkGos.checked == false){

        // Гос пошлина оплачена
        const totalDiv = document.createElement("div");
        totalDiv.innerHTML = 'Госпошлина: ' + '<b class="text-red-1">' + prices +'</b>'+ ' руб.';
        totalDiv.className = 'mt-3 font-bold text-base mob_l:text-lg text-pretty';
        wrap.append(totalDiv);

        const totalCommission = document.createElement("div");
        totalCommission.innerHTML = 'Комиссия: ' + '<b class="text-red-1">' + parseInt(commission.getAttribute('data-price')) +'</b>'+ ' руб.';
        totalCommission.className = 'font-bold text-lg';
        wrap.append(totalCommission);

    } else {
        
        document.querySelectorAll('[data-prices]').forEach(element => {
            element.classList.remove('row-price-active');
        })

        let comissData = document.querySelector('[data-prices="8"]');

        const comiss = document.createElement("div");
        comiss.innerHTML = comissData.getAttribute('data-title');  
        wrap.append(comiss); 

        totalprices = parseInt(commission.getAttribute('data-price'));

    }    

    const totalDivTotal = document.createElement("div");
    totalDivTotal.innerHTML = 'Итого: ' + '<b class="text-red-1">'+ totalprices +'</b>'+ ' руб.';
    totalDivTotal.className = 'font-bold text-base mob_l:text-lg text-pretty';
    wrap.append(totalDivTotal);
    
}

// Получаем Partial
window.getPartial = function(el){    

    let options = [];
    options['container'] = el.getAttribute('data-id');
    options['partial'] = el.getAttribute('data-partial');
    options['value'] = el.value;
    options['form'] = el.getAttribute('data-form');   

    if(el.checked == false){
        options['value'] = null;    
    }
    
    oc.ajax('onGetPartial', {
        data: options,
        success: function(data) {
            this.success(data).done(function() {
                console.log(data);
            });
        }
    })
       

}

// Копируем поле
window.cloneInput = function(el, event){
    event.preventDefault();

    // DIV для поля
    let div = document.createElement('div');
    div.className = 'flex gap-x-2';

    // Кнопка удаления
    let btnremove = document.createElement('button');
    btnremove.className = 'btn btn-red';
    btnremove.innerText = 'x';
    btnremove.setAttribute('onclick', 'deleteInputRow(this); return false;');

    let id = el.getAttribute('data-id');
    let wrap = document.getElementById(id);
    let input = wrap.querySelector('input');

    const clone = input.cloneNode(true);
    clone.value = null;

    // Вкладываем поле в DIV
    div.append(clone);  

    // Вкладываем button в DIV
    div.append(btnremove); 

    // Добвляем поле в конец списка
    wrap.append(div);      

    return false;
}

// Удаялем поле
window.deleteInputRow = function(el){
    el.parentElement.remove();
}

// Отключаем поля в блоке
window.refreshBoxInputs = function(id){
    let box = document.getElementById(id);

    box.classList.toggle('hidden');

    box.querySelectorAll('input').forEach(el => {
        if(box.classList.contains('hidden') == false){
            el.removeAttribute('disabled');    
        } else {            
            el.setAttribute('disabled', true);
        }        
    })
    
}

// Применение гос номеров
window.gosNumInput = function(el){

    let parent = el.parentElement;
    let value = parseInt(el.getAttribute('data-num'));
    let div = document.querySelector('[data-gos-nums="'+value+'"]');

    // Только текст
    if(el.getAttribute('type') == 'text'){
        el.value = el.value.slice(0,1).toUpperCase().replace(/[^а-яё\s]/gi, '');
    }
    
    div.innerHTML = el.value.toUpperCase();   

    if(el.value){
        parent.querySelector('[data-num="'+(value+1)+'"]').focus();
    }
    

}

// Выбор стоимости
window.selectTypePrice = function(el){

    let id = el.getAttribute('data-id');

    document.querySelectorAll('.data-input-row').forEach(row => {
        row.querySelector('input').setAttribute('disabled', true);   
        row.classList.add('hidden');
    })

    document.querySelectorAll('[data-input-id="'+id+'"]').forEach(row => {
        row.querySelector('input').removeAttribute('disabled');
        row.classList.remove('hidden');
    })

}

// Применение гос номеров регион
window.gosNumInputRegion = function(el){
    let value = el.getAttribute('data-num');
    let div = document.querySelector('[data-gos-nums="'+value+'"]');
    el.value = el.value.slice(0,3);
    div.innerHTML = el.value;
}

// Пошаговая форма
window.stepFormInput = function(input){
    let maxlength = input.getAttribute('maxlength');

    if(input.value.length == maxlength){
        const event = document.dispatchEvent(new KeyboardEvent('keydown', {
            key: 'Tab',
            code: 'Tab',
        }));
        input.dispatchEvent(event);
    };

    
}

// Карусель гос номеров
window.numberCarousel = function(){    
    const container = document.getElementById("numberCarousel");
    if(container){
        const options = {
            Autoscroll: {
                speedOnHover: 0
            }            
        };
        Carousel(container, options, { Autoscroll }).init();
    }    
}

// Лента гос номеров
window.numberCarouselLenta = function(){
    const container = document.getElementById("numberCarouselLenta");
    if(container){
        const options = {
            Autoscroll: {
                speedOnHover: 0
            }            
        };
        Carousel(container, options, { Autoscroll }).init();
    }   
}

// Карусель гос номеров вертикальная
window.numberCarouselVertical = function(){    
    const containerV = document.getElementById("numberCarouselVertical");
    if(containerV){
        const options = {
            vertical: true          
        };
        Carousel(containerV, options).init();
    }    
}