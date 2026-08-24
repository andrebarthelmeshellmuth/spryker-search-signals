import register from 'ShopUi/app/registry';

export default register(
    'search-signals-click-tracker',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "search-signals-click-tracker" */
            './click-tracker'
        ),
);
