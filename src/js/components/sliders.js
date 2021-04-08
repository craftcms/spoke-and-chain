import Swiper, {Navigation, Pagination, Keyboard} from 'swiper';
Swiper.use([Navigation, Pagination, Keyboard]);

window.sliders = function() {
    let sliderElements = document.querySelectorAll('[data-slider]');
    let sliders = {};

    if (!sliderElements.length) {
        return;
    }

    sliderElements.forEach(slider => {
        let opts = JSON.parse(slider.dataset.slider);

        sliders[slider.getAttribute('id')] = new Swiper(slider, opts);

        const ro = new ResizeObserver(entries => {
            for (let entry of entries) {
                sliders[slider.getAttribute('id')].updateSize();
            }
        });

        ro.observe(slider);
    });
};