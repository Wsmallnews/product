/**
 * 详情锚点 tab：滚动到对应区块（.tab-content）时高亮 tab
 * （tabs.item 的 alpine-active 消费 currentTab）
 */
export default function productTabs() {
    return {
        currentTab: null,

        init() {
            window.addEventListener('scroll', () => {
                this.$root.querySelectorAll('.tab-content').forEach((tabContent) => {
                    const rect = tabContent.getBoundingClientRect();

                    if (rect.top <= 100 && rect.bottom >= 100) {
                        this.currentTab = tabContent.getAttribute('id');
                    }
                });
            });
        },
    };
}
