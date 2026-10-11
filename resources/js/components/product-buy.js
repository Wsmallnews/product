/**
 * 商品购买盒（规格选择 + 附加项 + 数量 + 立即购买）——纯客户端本地交互，零 Livewire 请求。
 *
 * 消费 specSchema 契约（Product 组件 render 输出，与将来 uniapp 接口同构）：
 *   specs     规格组树（id/name/children[id/name/image]）
 *   variants  可售变体（id/spec_ids/spec_text/price 分/price_parts 展示件/stock）
 *   extras    附加项组（id/name/type single|multiple/is_require/is_num/options[id/name/price 分/price_parts/sold_out/limit_*_num/is_default]）
 *
 * 算法与服务端原 getSpecSelectable / getChoosedVariant 同构：
 *   - isSelectable：排除本组选择后，某规格值能否与其它组当前选择组合出可售变体
 *   - matchedVariant：全部规格组选中后，spec_ids 集合与所选一致的变体
 * 附加项同样本地选择/计价（售罄置灰不可选）；服务端 buy()/下单管道始终按 DB 重新校验，
 * 本地展示态不作信任来源。
 */
export default function productBuy({ schema, initialPriceParts = null, initialPriceMinor = 0, mainImageUrl = null, currencySymbol = '¥', langRemainingStock = '仅剩 :num 件' }) {
    return {
        schema,
        selected: {},          // parentId => childId（规格）
        selectedExtras: {},    // extraGroupId => optionId[]（附加项）
        extraNums: {},         // optionId => num（数量型附加项）
        specModes: {},         // parentId => 'list' | 'grid'（带图规格组形态，默认 list；无图组恒胶囊）
        productNum: 1,
        initialPriceParts,
        initialPriceMinor,
        mainImageUrl,
        currencySymbol,
        langRemainingStock,

        init() {
            // 默认选中的附加项（is_default 且未售罄）
            (this.schema.extras || []).forEach((group) => {
                group.options.forEach((option) => {
                    if (option.is_default && !option.sold_out) {
                        this.selectExtraOption(group, option);
                    }
                });
            });
        },

        /**
         * 规格值是否可选（其他规格组当前选择约束下）
         */
        isSelectable(parentId, childId) {
            const constraints = [];

            Object.keys(this.selected).forEach((pid) => {
                if (Number(pid) !== parentId) {
                    constraints.push(this.selected[pid]);
                }
            });

            return this.schema.variants.some((variant) =>
                variant.spec_ids.includes(childId)
                && constraints.every((specId) => variant.spec_ids.includes(specId))
            );
        },

        isSelected(parentId, childId) {
            return this.selected[parentId] === childId;
        },

        /**
         * 带图规格组的展示形态（list / grid）
         */
        specMode(parentId) {
            return this.specModes[parentId] || 'list';
        },

        setSpecMode(parentId, mode) {
            this.specModes[parentId] = mode;
        },

        /**
         * 切换规格选择（再次点击已选值 = 取消选中）
         */
        toggleSpec(parentId, childId) {
            if (this.selected[parentId] === childId) {
                delete this.selected[parentId];
            } else {
                this.selected[parentId] = childId;
            }
        },

        /**
         * 命中变体：单规格直接取首个；多规格需全部规格组均已选择
         */
        get matchedVariant() {
            const specs = this.schema.specs;

            if (!specs || specs.length === 0) {
                return this.schema.variants[0] ?? null;
            }

            if (!specs.every((group) => this.selected[group.id])) {
                return null;
            }

            const selectedIds = specs
                .map((group) => this.selected[group.id])
                .sort((a, b) => a - b);

            return this.schema.variants.find((variant) => {
                const variantIds = [...variant.spec_ids].sort((a, b) => a - b);

                return variantIds.length === selectedIds.length
                    && variantIds.every((id, index) => id === selectedIds[index]);
            }) ?? null;
        },

        get selectedVariantId() {
            return this.matchedVariant?.id ?? 0;
        },

        /**
         * 价格展示件（符号/金额串）：命中变体取变体价，未命中回退商品初始价
         */
        get priceParts() {
            return this.matchedVariant?.price_parts ?? this.initialPriceParts;
        },

        /**
         * 规格值在该值下的最低变体价（列表形态卡片展示用）：无图组不展示价差，返回 null
         */
        childMinPriceParts(childId) {
            const prices = this.schema.variants
                .filter((variant) => variant.spec_ids.includes(childId))
                .map((variant) => variant.price);

            return prices.length ? this.currencySymbol + (Math.min(...prices) / 100).toFixed(2) : null;
        },

        /**
         * 组合图自动推导：变体覆盖图 > 首个带图规格组当前选中值图 > 商品主图
         */
        get variantImage() {
            if (this.matchedVariant?.image) {
                return this.matchedVariant.image;
            }

            const imageGroup = (this.schema.specs || []).find((group) => group.children.some((child) => child.image));
            if (imageGroup && this.selected[imageGroup.id]) {
                const child = imageGroup.children.find((c) => c.id === this.selected[imageGroup.id]);

                if (child?.image) {
                    return child.image;
                }
            }

            return this.mainImageUrl;
        },

        /**
         * 已选规格回显串：全部选完 = 变体 spec_text；部分选择 = 已选组值名拼接；未选 = 空串
         */
        get selectedSpecText() {
            if (this.matchedVariant) {
                return this.matchedVariant.spec_text?.join(' / ');
            }

            return (this.schema.specs || [])
                .filter((group) => this.selected[group.id])
                .map((group) => group.children.find((child) => child.id === this.selected[group.id])?.name)
                .filter(Boolean)
                .join(' / ');
        },

        /**
         * 低库存提示（仅库存型且 ≤3 件）：null = 不展示
         */
        get stockHint() {
            const stock = this.matchedVariant?.stock;

            return (stock !== null && stock !== undefined && stock <= 3) ? stock : null;
        },

        /**
         * 低库存提示串（翻译模板由服务端传入，:num 占位）
         */
        get remainingStockText() {
            return this.stockHint === null ? '' : this.langRemainingStock.replace(':num', this.stockHint);
        },

        /**
         * 结算合计（分）：变体价（未命中回退初始价）+ 附加项，× 数量
         */
        get totalMinor() {
            return ((this.matchedVariant?.price ?? this.initialPriceMinor) + this.extrasTotalMinor) * this.productNum;
        },

        get totalText() {
            return this.currencySymbol + (this.totalMinor / 100).toFixed(2);
        },

        // ========================= 附加项（加料/增值服务） =========================

        extraGroup(groupId) {
            return (this.schema.extras || []).find((group) => group.id === groupId);
        },

        extraOption(groupId, optionId) {
            return this.extraGroup(groupId)?.options.find((option) => option.id === optionId) ?? null;
        },

        isExtraSelected(groupId, optionId) {
            return (this.selectedExtras[groupId] || []).includes(optionId);
        },

        selectExtraOption(group, option) {
            if (group.type === 'single') {
                this.selectedExtras[group.id] = [option.id];
            } else {
                this.selectedExtras[group.id] = [...(this.selectedExtras[group.id] || []), option.id];
            }

            if (group.is_num) {
                this.extraNums[option.id] = this.extraNums[option.id] ?? option.limit_min_num;
            }
        },

        toggleExtraOption(groupId, optionId) {
            const option = this.extraOption(groupId, optionId);
            if (!option || option.sold_out) {
                return;
            }

            if (this.isExtraSelected(groupId, optionId)) {
                this.selectedExtras[groupId] = this.selectedExtras[groupId].filter((id) => id !== optionId);
            } else {
                this.selectExtraOption(this.extraGroup(groupId), option);
            }
        },

        /**
         * 附加项单份合计（分）
         */
        get extrasTotalMinor() {
            let total = 0;

            Object.entries(this.selectedExtras).forEach(([groupId, optionIds]) => {
                optionIds.forEach((optionId) => {
                    const option = this.extraOption(Number(groupId), optionId);
                    if (!option) {
                        return;
                    }

                    const num = this.extraGroup(Number(groupId))?.is_num ? (this.extraNums[optionId] ?? 1) : 1;
                    total += option.price * num;
                });
            });

            return total;
        },

        /**
         * 附加项合计展示串（¥12.00）
         */
        get extrasTotalText() {
            return this.currencySymbol + (this.extrasTotalMinor / 100).toFixed(2);
        },

        /**
         * 附加项摘要串（珍珠×2 · 奶盖）：数量型带份数，单份只名称
         */
        get extrasText() {
            const parts = [];

            Object.entries(this.selectedExtras).forEach(([groupId, optionIds]) => {
                const group = this.extraGroup(Number(groupId));

                optionIds.forEach((optionId) => {
                    const option = this.extraOption(Number(groupId), optionId);
                    if (!option) {
                        return;
                    }

                    const num = group?.is_num ? (this.extraNums[optionId] ?? 1) : 1;
                    parts.push(num > 1 ? `${option.name}×${num}` : option.name);
                });
            });

            return parts.join(' · ');
        },

        /**
         * 购买载荷的附加项结构（与下单管道入参一致）：[{id: 组, children: [{id, num}]}]
         */
        get extrasPayload() {
            return Object.entries(this.selectedExtras)
                .filter(([, optionIds]) => optionIds.length > 0)
                .map(([groupId, optionIds]) => ({
                    id: Number(groupId),
                    children: optionIds.map((optionId) => ({
                        id: optionId,
                        num: this.extraGroup(Number(groupId))?.is_num ? (this.extraNums[optionId] ?? 1) : 1,
                    })),
                }));
        },
    };
}
