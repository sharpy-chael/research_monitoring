import { useState, useEffect } from "react";
import React from "react";
import { LineGraph } from "./line.jsx";
import { PieChart } from "./pie.jsx";
import "./App.css";

function App() {
  const [chartData, setChartData] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch("http://localhost/research_monitoring/get_data.php");
        const data = await res.json();
        
        setChartData(data);
        console.log("Fetched data:", data);
        // Keep your PHP progress bar in sync
        const completed = data.pie.data[0];
        const bar = document.getElementById("progress-bar-fill");
        const text = document.getElementById("progress-text");
        if (bar && text) {
          bar.style.width = `${completed}%`;
          text.textContent = `${completed.toFixed(2)}% completed`;
        }
      } catch (err) {
        console.error("Error fetching data:", err);
      }
    };

    fetchData();
    const interval = setInterval(fetchData, 5000);
    return () => clearInterval(interval);
  }, []);

  if (!chartData) return <p>Loading charts...</p>;

return (
  <div className="chart-wrapper">
    <div className="chart-row">
      <div className="chart-box">
        <h2>Line Chart</h2>
        <LineGraph data={chartData.line} />
      </div>
      <div className="chart-box">
        <h2>Pie Chart</h2>
        <PieChart data={chartData.pie} />
      </div>
    </div>
  </div>
);
}

export default App;
