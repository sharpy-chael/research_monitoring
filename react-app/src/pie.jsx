import { Pie } from "react-chartjs-2";
import { Chart as ChartJS, Tooltip, Legend, ArcElement } from "chart.js";

ChartJS.register(Tooltip, Legend, ArcElement);

export const PieChart = ({ data }) => {
  const options = {
    responsive: true, 
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: 'Task Status Distribution'
      }
    }
  };

  const chartData = {
    labels: data.labels,
    datasets: [
      {
        label: "Tasks",
        data: data.data,
        backgroundColor: [
          "rgba(76, 175, 80, 0.8)",   // Approved - Green
          "rgba(255, 193, 7, 0.8)",   // Pending - Yellow
          "rgba(244, 67, 54, 0.8)",   // Rejected - Red
          "rgba(158, 158, 158, 0.8)"  // Missing - Gray
        ],
        borderColor: [
          "rgba(76, 175, 80, 1)",
          "rgba(255, 193, 7, 1)",
          "rgba(244, 67, 54, 1)",
          "rgba(158, 158, 158, 1)"
        ],
        borderWidth: 2,
        hoverOffset: 4,
      },
    ],
  };

  return (
    <div className="pie-chart" style={{ height: "300px" }}>
      <Pie options={options} data={chartData} />
    </div>
  );
};