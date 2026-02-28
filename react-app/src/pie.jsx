import { Pie } from "react-chartjs-2";
import { Chart as ChartJS, Tooltip, Legend, ArcElement } from "chart.js";

ChartJS.register(Tooltip, Legend, ArcElement);

export const PieChart = ({ data }) => {
  const isCoordinatorView = data.labels && data.labels.length === 9 && 
    data.labels.some(label => label.includes('Approved Titles') || label.includes('Proposal'));
  
  const isStudentAdvisorView = data.labels && data.labels.length <= 4 && 
    (data.labels.includes('Approved') || data.labels.includes('Pending'));

  const options = {
    responsive: true, 
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: isCoordinatorView ? 'right' : 'top',
        labels: {
          boxWidth: 15,
          font: {
            size: isCoordinatorView ? 11 : 12
          }
        }
      },
      title: {
        display: true,
        text: isCoordinatorView ? 'Milestone Completion Status' : 'Task Status Distribution'
      }
    }
  };

  const getColors = () => {
    if (isCoordinatorView) {
      return {
        backgroundColor: [
          "rgba(76, 175, 80, 0.8)",
          "rgba(33, 150, 243, 0.8)",
          "rgba(156, 39, 176, 0.8)",
          "rgba(233, 30, 99, 0.8)",
          "rgba(255, 152, 0, 0.8)",
          "rgba(255, 87, 34, 0.8)",
          "rgba(121, 85, 72, 0.8)",
          "rgba(96, 125, 139, 0.8)",
          "rgba(0, 150, 136, 0.8)"
        ],
        borderColor: [
          "rgba(76, 175, 80, 1)",
          "rgba(33, 150, 243, 1)",
          "rgba(156, 39, 176, 1)",
          "rgba(233, 30, 99, 1)",
          "rgba(255, 152, 0, 1)",
          "rgba(255, 87, 34, 1)",
          "rgba(121, 85, 72, 1)",
          "rgba(96, 125, 139, 1)",
          "rgba(0, 150, 136, 1)"
        ]
      };
    } else {
      return {
        backgroundColor: [
          "rgba(76, 175, 80, 0.8)",
          "rgba(255, 193, 7, 0.8)",
          "rgba(244, 67, 54, 0.8)",
          "rgba(158, 158, 158, 0.8)"
        ],
        borderColor: [
          "rgba(76, 175, 80, 1)",
          "rgba(255, 193, 7, 1)",
          "rgba(244, 67, 54, 1)",
          "rgba(158, 158, 158, 1)"
        ]
      };
    }
  };

  const colors = getColors();

  const chartData = {
    labels: data.labels,
    datasets: [
      {
        label: isCoordinatorView ? "Milestones" : "Tasks",
        data: data.data,
        backgroundColor: colors.backgroundColor,
        borderColor: colors.borderColor,
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