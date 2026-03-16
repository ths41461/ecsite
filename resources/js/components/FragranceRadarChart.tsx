import ReactApexCharts from 'react-apexcharts';
import type { ApexOptions } from 'apexcharts';

type RadarData = {
    sweetness: number;
    freshness: number;
    floral: number;
    woody: number;
    spicy: number;
    musky: number;
};

type Props = {
    radarData: RadarData;
};

const dimensionConfig = {
    sweetness: { label: '甘さ', color: '#EAB308', description: 'Sweet' },
    freshness: { label: '爽やかさ', color: '#363842', description: 'Fresh' },
    floral: { label: '花', color: '#EC4899', description: 'Floral' },
    woody: { label: '木', color: '#8B7355', description: 'Woody' },
    spicy: { label: 'スパイス', color: '#D97706', description: 'Spicy' },
    musky: { label: 'ムスク', color: '#6B7280', description: 'Musky' },
};

export default function FragranceRadarChart({ radarData }: Props) {
    const dataValues = [
        radarData.sweetness,
        radarData.freshness,
        radarData.floral,
        radarData.woody,
        radarData.spicy,
        radarData.musky,
    ];

    const categories = Object.values(dimensionConfig).map(d => d.label);
    
    // Use a neutral color for the chart - the dimension colors are shown in the legend below
    const chartColor = '#EAB308';

    const options: ApexOptions = {
        chart: {
            type: 'radar',
            height: 400,
            toolbar: {
                show: false,
            },
            animations: {
                enabled: true,
                easing: 'easeInOutQuart',
                speed: 1000,
                animateGradually: {
                    enabled: true,
                    delay: 100,
                },
            },
        },
        xaxis: {
            categories: categories,
            labels: {
                show: true,
                style: {
                    fontFamily: 'Noto Sans JP, Hiragino Sans, sans-serif',
                    fontSize: '13px',
                    fontWeight: '500',
                    colors: '#444444',
                },
            },
        },
        yaxis: {
            show: false,
            min: 0,
            max: 100,
            tickAmount: 5,
        },
        plotOptions: {
            radar: {
                polygons: {
                    strokeColors: '#E5E7EB',
                    strokeWidth: 1,
                    connectorColors: '#E5E7EB',
                    fill: {
                        colors: undefined,
                    },
                },
            },
        },
        fill: {
            opacity: 0.4,
            colors: [chartColor],
            type: 'solid',
        },
        stroke: {
            show: true,
            width: 2,
            colors: [chartColor],
            dashArray: 0,
            lineCap: 'round',
        },
        markers: {
            size: 5,
            colors: [chartColor],
            strokeColors: '#FFFFFF',
            strokeWidth: 2,
            shape: 'circle',
            hover: {
                size: 9,
                strokeColors: '#FFFFFF',
                strokeWidth: 2,
            },
        },
        tooltip: {
            enabled: true,
            shared: false,
            followCursor: true,
            theme: 'light',
            fixed: {
                enabled: false,
            },
            style: {
                fontFamily: 'Noto Sans JP, Hiragino Sans, sans-serif',
                fontSize: '13px',
            },
            x: {
                show: true,
                formatter: (value: string, opts: { dataPointIndex: number }) => {
                    const key = Object.keys(dimensionConfig)[opts.dataPointIndex] as keyof typeof dimensionConfig;
                    const color = dimensionConfig[key].color;
                    return `<span style="color: ${color}; font-weight: 600;">● ${dimensionConfig[key].label}</span>`;
                },
            },
            y: {
                formatter: (value: number) => `${Math.round(value)}/100`,
                title: {
                    formatter: () => '',
                },
            },
            marker: {
                show: false,
            },
        },
        legend: {
            show: false,
        },
        title: {
            text: '香りプロファイル',
            align: 'center',
            style: {
                fontFamily: 'Hiragino Mincho ProN, Yu Mincho, serif',
                fontSize: '18px',
                fontWeight: '600',
                color: '#363842',
            },
            offsetY: 10,
        },
        subtitle: {
            text: 'Fragrance Profile',
            align: 'center',
            style: {
                fontFamily: 'Noto Sans JP, Hiragino Sans, sans-serif',
                fontSize: '12px',
                color: '#888888',
            },
            offsetY: 32,
        },
        grid: {
            padding: {
                top: 20,
                right: 20,
                bottom: 10,
                left: 20,
            },
        },
        dataLabels: {
            enabled: false,
        },
        colors: [chartColor],
    };

    const series = [
        {
            name: '香りプロファイル',
            data: dataValues,
        },
    ];

    return (
        <div className="w-full bg-[#FCFCF7] border border-[#EEDDD4] p-6">
            <ReactApexCharts options={options} series={series} type="radar" height={400} />
            <div className="mt-4 grid grid-cols-3 md:grid-cols-6 gap-2">
                {(Object.entries(radarData) as [keyof typeof radarData, number][]).map(([key, value]) => {
                    const config = dimensionConfig[key];
                    return (
                        <div 
                            key={key}
                            className="flex flex-col items-center p-2"
                        >
                            <div className="flex items-center gap-1.5 mb-1">
                                <div 
                                    className="w-2.5 h-2.5 rounded-full" 
                                    style={{ backgroundColor: config.color }}
                                />
                                <span className="text-xs font-medium text-gray-700">{config.label}</span>
                            </div>
                            <div className="flex items-baseline gap-0.5">
                                <span className="text-base font-semibold text-gray-900">{Math.round(value)}</span>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
