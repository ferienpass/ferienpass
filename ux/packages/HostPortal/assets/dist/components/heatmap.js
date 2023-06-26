"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const frappe_charts_esm_js_1 = require("frappe-charts/dist/frappe-charts.esm.js");
exports.default = (values, start) => {
    return {
        chart: null,
        init() {
            this.chart = new frappe_charts_esm_js_1.Chart(this.$refs.chart, {
                type: "heatmap", colors: ["#ebedf0", "#c0ddf9", "#73b3f3", "#3886e1", "#17459e"],
                data: {
                    dataPoints: values,
                    start: start,
                    end: new Date()
                },
            });
        }
    };
};
