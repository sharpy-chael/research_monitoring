import React, { useEffect, useState } from "react";
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

function App() {
  const [chartData, setChartData] = useState(null);

  useEffect(() => {
    fetch("http://localhost/myapp/api/getData.php")
      .then(res => res.json())
      .then(data => {
        const labels = data.map(item => item.label);
        const values = data.map(item => item.value);
        setChartData({
          labels,
          datasets: [
            {
              label: "Data from MySQL",
              data: values,
              backgroundColor: ["#36A2EB", "#FF6384", "#FFCE56"],
            },
          ],
        });
      });
  }, []);

  if (!chartData) return <p>Loading chart...</p>;

  return <Bar data={chartData} />;
}

export default App;
