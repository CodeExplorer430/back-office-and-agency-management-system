(() => {
    const dataElement = document.getElementById('ct2-dashboard-chart-data');
    if (!dataElement || typeof ApexCharts === 'undefined') {
        return;
    }

    let dashboardData;
    try {
        dashboardData = JSON.parse(dataElement.textContent || '{}');
    } catch (error) {
        return;
    }

    const chartFont = getComputedStyle(document.documentElement).getPropertyValue('--tblr-font-sans-serif').trim() || 'inherit';
    const palette = ['#8fe07f', '#7fe0a0', '#c0e07f', '#2f6b42'];

    const renderChart = (selector, options) => {
        const element = document.querySelector(selector);
        if (!element) {
            return;
        }

        const chart = new ApexCharts(element, options);
        chart.render();
    };

    renderChart('#ct2-system-flow-chart', {
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: chartFont,
        },
        series: [
            {
                name: 'Flow load',
                data: dashboardData.systemFlow?.values ?? [],
            },
        ],
        colors: [palette[1]],
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 4 },
        legend: { show: false },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '46%',
            },
        },
        stroke: {
            width: 0,
        },
        tooltip: {
            theme: 'light',
        },
        xaxis: {
            categories: dashboardData.systemFlow?.categories ?? [],
        },
        yaxis: {
            labels: {
                formatter(value) {
                    return String(Math.round(value));
                },
            },
        },
    });

    renderChart('#ct2-department-chart', {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            fontFamily: chartFont,
        },
        series: [
            {
                name: 'Readiness',
                data: dashboardData.departmentReadiness?.values ?? [],
            },
        ],
        colors: [palette[0]],
        dataLabels: { enabled: false },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.34,
                opacityTo: 0.06,
                stops: [0, 90, 100],
            },
        },
        grid: { strokeDashArray: 4 },
        markers: {
            size: 4,
            strokeWidth: 0,
            hover: { size: 6 },
        },
        stroke: {
            curve: 'smooth',
            width: 3,
        },
        tooltip: {
            theme: 'light',
        },
        xaxis: {
            categories: dashboardData.departmentReadiness?.categories ?? [],
        },
        yaxis: {
            labels: {
                formatter(value) {
                    return String(Math.round(value));
                },
            },
        },
    });
})();
