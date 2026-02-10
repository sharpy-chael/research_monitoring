import { Line } from "react-chartjs-2";
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from "chart.js";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export const LineGraph = ({ data }) => {
  // Determine chart type based on data structure
  const isStudentView = !data.datasets && data.data;
  const isAdvisorView = data.datasets && data.datasets.length > 0 && data.datasets[0]?.label !== "Approved" && data.datasets[0]?.label !== "Pending" && data.datasets[0]?.label !== "Rejected";
  const isCoordinatorView = data.datasets && data.datasets.length > 0 && (
    data.datasets.some(ds => ds.label === "Approved") ||
    data.datasets.some(ds => ds.label === "Rejected")
  );

  // Coordinator view now uses percentages
  const isPercentage = isStudentView || isAdvisorView || isCoordinatorView;
  
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: "top" },
      title: {
        display: true,
        text: isStudentView 
          ? "Research Progress Over Time" 
          : isAdvisorView 
            ? "Group Progress Comparison" 
            : "Submission Status Percentages Over Time",
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

  // Handle both single dataset (student) and multiple datasets (advisor/coordinator)
  let chartData;
  
  if (data.datasets) {
    // Advisor/Coordinator view: multiple lines
    chartData = {
      labels: data.labels,
      datasets: data.datasets
    };
  } else {
    // Student view: single line
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
