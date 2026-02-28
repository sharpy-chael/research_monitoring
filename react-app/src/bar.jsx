import { Bar } from "react-chartjs-2";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from "chart.js";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

// Colors matching get_coordinator_data.php $colors array (same order as milestoneLabels)
const MILESTONE_COLORS = [
  { bg: "rgba(76, 175, 80, 0.75)",  border: "rgb(76, 175, 80)"  },  // Approved Titles
  { bg: "rgba(33, 150, 243, 0.75)", border: "rgb(33, 150, 243)" },  // Proposal
  { bg: "rgba(156, 39, 176, 0.75)", border: "rgb(156, 39, 176)" },  // UREC Processing
  { bg: "rgba(233, 30, 99, 0.75)",  border: "rgb(233, 30, 99)"  },  // UREC Clearance
  { bg: "rgba(255, 152, 0, 0.75)",  border: "rgb(255, 152, 0)"  },  // Final Defense
  { bg: "rgba(255, 87, 34, 0.75)",  border: "rgb(255, 87, 34)"  },  // Applied for Copyright
  { bg: "rgba(121, 85, 72, 0.75)",  border: "rgb(121, 85, 72)"  },  // Research Presented
  { bg: "rgba(96, 125, 139, 0.75)", border: "rgb(96, 125, 139)" },  // Research Published
  { bg: "rgba(0, 150, 136, 0.75)",  border: "rgb(0, 150, 136)"  },  // Copyright Approved
];

export const BarGraph = ({ data }) => {
  // data = { labels: string[], data: number[] }
  // labels are the 9 milestone names, data are the raw completion counts

  const chartData = {
    labels: data.labels,
    datasets: [
      {
        label: "Groups Completed",
        data: data.data,
        backgroundColor: data.labels.map((_, i) => MILESTONE_COLORS[i % MILESTONE_COLORS.length].bg),
        borderColor:     data.labels.map((_, i) => MILESTONE_COLORS[i % MILESTONE_COLORS.length].border),
        borderWidth: 1.5,
        borderRadius: 6,
        borderSkipped: false,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      title: {
        display: true,
        text: "Milestone Completion — Frequency",
        font: { size: 14, weight: "600" },
        color: "#333",
        padding: { bottom: 16 },
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const count = context.parsed.y;
            return ` Frequency: ${count}`;
          },
        },
      },
    },
    scales: {
      x: {
        ticks: {
          font: { size: 11 },
          maxRotation: 35,
          minRotation: 20,
          color: "#555",
        },
        grid: { display: false },
      },
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1,
          precision: 0,
          color: "#555",
          font: { size: 11 },
          callback: (v) => (Number.isInteger(v) ? v : ""),
        },
        grid: {
          color: "rgba(0,0,0,0.06)",
        },
        title: {
          display: true,
          text: "Frequency",
          font: { size: 12, weight: "600" },
          color: "#666",
        },
      },
    },
  };

  return (
    <div style={{ height: "320px" }}>
      <Bar options={options} data={chartData} />
    </div>
  );
};