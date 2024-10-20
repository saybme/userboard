// Окно добавление темы обращения
function modalSubTheme(data) {

    new Fancybox(
        [
            {
                src: data.modal,
                type: "html",
            },
        ],
        {
            showClass: 'ub-modal-open'
        }
    );

}