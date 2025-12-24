import { Line } from "react-chartjs-2";
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from "chart.js";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export const LineGraph = ({ data }) => {
  // Dynamic options based on data type
  const isPercentage = data.datasets && data.datasets[0]?.label === "Progress";
  
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: "top" },
      title: {
        display: true,
        text: isPercentage ? "Research Progress Over Time" : "Submission Activity Over Time",
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ...(isPercentage && {
          min: 0,
          max: 100,
          ticks: {
            callback: function(value) {
              return value + '%';
            }
          }
        })
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