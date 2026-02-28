import { Line } from "react-chartjs-2";
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from "chart.js";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export const LineGraph = ({ data }) => {
  const isStudentView = !data.datasets && data.data;
  const isCoordinatorView = data.datasets && data.datasets.length === 9 && 
    data.datasets.some(ds => ds.label && (ds.label.includes('Approved Titles') || ds.label.includes('Proposal')));
  const isAdvisorView = data.datasets && data.datasets.length > 0 && !isCoordinatorView &&
    data.datasets[0]?.label && data.datasets[0].label !== "Approved" && data.datasets[0].label !== "Pending";

  const isPercentage = isStudentView || isAdvisorView || isCoordinatorView;
  
  const getChartTitle = () => {
    if (isStudentView) return "Research Progress Over Time";
    if (isAdvisorView) return "Group Progress Comparison";
    if (isCoordinatorView) return "Milestone Completion Over Time";
    return "Submission Status Percentages Over Time";
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { 
        position: "top",
        labels: {
          boxWidth: 15,
          font: {
            size: isCoordinatorView ? 10 : 12
          }
        }
      },
      title: {
        display: true,
        text: getChartTitle(),
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.dataset.label || '';
            if (label) {
              label += ': ';
            }
            if (context.parsed.y !== null) {
              label += context.parsed.y.toFixed(2) + '%';
            }
            return label;
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        min: 0,
        max: 100,
        ticks: {
          callback: function(value) {
            return value + '%';
          },
          stepSize: 10
        }
      },
    },
  };

  let chartData;
  
  if (data.datasets) {
    chartData = {
      labels: data.labels,
      datasets: data.datasets
    };
  } else {
    chartData = {
      labels: data.labels,
      datasets: [
        {
          label: "Progress",
          data: data.data,
          borderColor: "rgb(75, 192, 192)",
          backgroundColor: "rgba(75, 192, 192, 0.2)",
          fill: true,
          tension: 0.3,
        },
      ],
    };
  }

  return (
    <div style={{ height: "300px" }}>
      <Line options={options} data={chartData} />
    </div>
  );
};