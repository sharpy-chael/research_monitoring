import { useState, useEffect } from "react";
import React from "react";
import { LineGraph } from "./line.jsx";
import { PieChart } from "./pie.jsx";
import "./App.css";

function AppCoordinator() {
  const [chartData, setChartData] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch("http://localhost/research_monitoring/get_coordinator_data.php");
        const data = await res.json();
        
        setChartData(data);
        console.log("Coordinator data:", data);
        
        // Update progress bar (percentage of approved tasks)
        const approved = data.pie.data[0];
        const total = data.pie.data.reduce((sum, val) => sum + val, 0);
        const percentage = total > 0 ? ((approved / total) * 100).toFixed(2) : 0;
        
        const bar = document.getElementById("progress-bar-fill");
        const text = document.getElementById("progress-text");
        if (bar && text) {
          bar.style.width = `${percentage}%`;
          text.textContent = `${percentage}% completed`;
        }
      } catch (err) {
        console.error("Error fetching coordinator data:", err);
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
          <h2>Submission Timeline</h2>
          <LineGraph data={chartData.line} />
        </div>
        <div className="chart-box">
          <h2>Overall Status Distribution</h2>
          <PieChart data={chartData.pie} />
        </div>
      </div>
    </div>
  );
}

export default AppCoordinator;