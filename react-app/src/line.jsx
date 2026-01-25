import { Line } from "react-chartjs-2";
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from "chart.js";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export const LineGraph = ({ data }) => {
  // Determine chart type based on data structure
  const isStudentView = !data.datasets && data.data;
  const isAdvisorView = data.datasets && data.datasets.length > 0 && data.datasets[0]?.label !== "Approved" && data.datasets[0]?.label !== "Pending" && data.datasets[0]?.label !== "Rejected";
  const isCoordinatorView = data.datasets && data.datasets.length > 0 && (
    data.datasets.some(ds => ds.label === "Approved") ||
    data.datasets.some(ds => ds.label === "Pending") ||
    data.datasets.some(ds => ds.label === "Rejected")
  );

  // Check if this is percentage-based (student/advisor) or count-based (coordinator)
  const isPercentage = isStudentView || isAdvisorView;
  
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
            : "Submission Activity Over Time",
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ...(isPercentage ? {
          // Percentage scale for student/advisor
          min: 0,
          max: 100,
          ticks: {
            callback: function(value) {
              return value + '%';
            },
            stepSize: 10
          }
        } : {
          // Count scale for coordinator - only show whole numbers
          ticks: {
            callback: function(value) {
              if (Math.floor(value) === value) {
                return value; // Only display if it's a whole number
              }
            },
            stepSize: 1
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