define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/translate'
], function ($, ko, Component, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            endpointUrl: '',
            refreshInterval: 5
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.qty = ko.observable(null);
            this.isSalable = ko.observable(false);
            this.isManageStock = ko.observable(true);
            this.isAvailable = ko.observable(false);
            this.label = ko.pureComputed(this.getLabel, this);

            this.refresh();
            setInterval(this.refresh.bind(this), this.refreshInterval * 1000);

            return this;
        },

        refresh: function () {
            $.ajax({
                url: this.endpointUrl,
                dataType: 'json',
                cache: true,
                global: false
            }).done(this.update.bind(this)).fail(this.hide.bind(this));
        },

        /**
         * @param {{qty: (Number|null), is_salable: Boolean, is_manage_stock: Boolean}} stockData
         */
        update: function (stockData) {
            this.qty(stockData['qty']);
            this.isSalable(stockData['is_salable']);
            this.isManageStock(stockData['is_manage_stock']);
            this.isAvailable(true);
        },

        hide: function () {
            this.isAvailable(false);
        },

        /**
         * @returns {String}
         */
        getLabel: function () {
            if (!this.isManageStock()) {
                return $t('In stock');
            }

            if (!this.isSalable()) {
                return $t('Out of stock');
            }

            return $t('%1 in stock').replace('%1', this.qty());
        }
    });
});
