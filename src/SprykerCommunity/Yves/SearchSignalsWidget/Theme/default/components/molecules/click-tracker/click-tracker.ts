import Component from 'ShopUi/models/component';

/**
 * Rewrites the PDP anchors of the product card this element sits next to, to the signed click-redirect
 * URL rendered into `data-click-url`. Progressive enhancement, not click interception: the anchors are
 * real hrefs from the start (pointing at the real PDP), so if this never runs (JS disabled, script
 * failure) the link still works correctly, just untracked -- matches the "no dead ends" behavior
 * ClickController itself already guarantees on the landing side.
 */
export default class SearchSignalsClickTracker extends Component {
    protected readyCallback(): void {
        const clickUrl = this.getAttribute('data-click-url');

        if (!clickUrl || !this.parentElement) {
            return;
        }

        this.parentElement
            .querySelectorAll<HTMLAnchorElement>('a[class*="link-detail-page"]')
            .forEach((anchor) => {
                anchor.href = clickUrl;
            });
    }
}
