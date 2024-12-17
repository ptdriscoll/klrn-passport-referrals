Chart.defaults.font.family =
  "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";

Chart.defaults.color = '#747474';

export const defaultColors = [
  'rgb(54, 162, 235, 0.5)',
  'rgb(255, 99, 132, 0.5)',
  'rgb(255, 159, 64, 0.5)',
  'rgb(255, 205, 86, 0.5)',
  'rgb(75, 192, 192, 0.5)',
  'rgb(153, 102, 255, 0.5)',
  'rgb(201, 203, 207, 0.5)',
  'rgb(54, 162, 235, 0.5)',
  'rgb(255, 99, 132, 0.5)',
  'rgb(255, 159, 64, 0.5)',
];

export const defaultColorsTrends = [
  'rgb(255, 99, 132, 0.5)',
  'rgb(54, 162, 235, 0.5)',
  'rgb(255, 159, 64, 0.5)',
];

export const defaultBackgroundsTrends = [
  'rgb(255, 99, 132, 0.05)',
  'rgb(54, 162, 235, 0.05)',
  'rgb(255, 159, 64, 0.05)',
];

//bar graphics
export const ctxBarShows = document.getElementById('bar-shows');
export const ctxBarEpisodes = document.getElementById('bar-episodes');
export const ctxBarGenres = document.getElementById('bar-genres');

export let barOptions = {
  plugins: {
    title: {
      display: true,
      text: 'Top Shows',
      font: {
        size: 22,
      },
    },
  },
  indexAxis: 'y',
  scales: {
    y: {
      beginAtZero: true,
    },
    x: {
      ticks: {
        precision: 0,
      },
    },
  },
  responsive: true,
  maintainAspectRatio: false,
};

export let barOptionsEpisodes = JSON.parse(JSON.stringify(barOptions));
barOptionsEpisodes.plugins.title.text = 'Top Episodes';

export let barOptionsGenres = JSON.parse(JSON.stringify(barOptions));
barOptionsGenres.plugins.title.text = 'Genre Rankings';

//trend and daily referrals graphics
export const ctxTrendsShows = document.getElementById('trends-shows');
export const ctxTrendsEpisodes = document.getElementById('trends-episodes');
export const ctxTrendsReferrals = document.getElementById('trends-referrals');

export const tooltipOptions = {
  callbacks: {
    label: function (ctx) {
      let label = ctx.dataset.label || '';
      if (label) label += ': ';
      if (ctx.parsed.y !== null) {
        label +=
          ctx.parsed.y.toLocaleString() +
          (ctx.parsed.y === 1 ? ' referral' : ' referrals');
      }
      return label;
    },
  },
};

export const trendsOptions = {
  plugins: {
    title: {
      display: true,
      text: 'Trending Shows',
      font: {
        size: 22,
      },
    },
    tooltip: tooltipOptions,
  },
  scales: {
    x: {
      type: 'time',
      time: {
        unit: 'day',
        tooltipFormat: 'eeee, MMM d, yyyy',
        displayFormats: {
          day: 'eee',
        },
      },
    },
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
      },
    },
  },
  responsive: true,
  maintainAspectRatio: false,
};
