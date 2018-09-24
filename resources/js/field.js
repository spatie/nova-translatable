Nova.booting(Vue => {
    Vue.component('index-nova-translatable-field', require('./components/Nova/IndexField'));
    Vue.component('detail-nova-translatable-field', require('./components/Nova/DetailField'));
    Vue.component('form-nova-translatable-field', require('./components/Nova/FormField'));
});
