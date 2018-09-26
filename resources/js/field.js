Nova.booting(Vue => {
    Vue.component('index-nova-translatable', require('./components/Nova/IndexField'));
    Vue.component('detail-nova-translatable', require('./components/Nova/DetailField'));
    Vue.component('form-nova-translatable', require('./components/Nova/FormField'));
});
