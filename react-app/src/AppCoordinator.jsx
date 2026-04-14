import { useState, useEffect } from "react";
import React from "react";
import { BarGraph } from "./bar.jsx";
import { PieChart } from "./pie.jsx";

import "./App.css";

function AppCoordinator() {
  const [chartData, setChartData] = useState(null);
  const [filters, setFilters] = useState({ program: 'all', advisor: 'all' });

  const fetchData = async (program = 'all', advisor = 'all') => {
    try {
      const res = await fetch(`/research_monitoring_system/data/get_coordinator_data.php?program=${program}&advisor=${advisor}`);
      const data = await res.json();
      
      setChartData(data);
      console.log("Coordinator data:", data);
      
      const bar = document.getElementById("progress-bar-fill");
      const text = document.getElementById("progress-text");
      const barEnhanced = document.getElementById("progress-bar-fill-enhanced");
      const textEnhanced = document.getElementById("progress-text-enhanced");
      
      if (bar && text) {
        bar.style.width = `${data.progress}%`;
        text.textContent = `${data.progress}%`;
      }
      if (barEnhanced && textEnhanced) {
        barEnhanced.style.width = `${data.progress}%`;
        textEnhanced.textContent = `${data.progress}%`;
      }
    } catch (err) {
      console.error("Error fetching coordinator data:", err);
    }
  };

  useEffect(() => {
    fetchData(filters.program, filters.advisor);
    
    const handleFilterChange = (event) => {
      const { program, advisor } = event.detail;
      setFilters({ program, advisor });
      fetchData(program, advisor);
    };
    
    window.addEventListener('filtersChanged', handleFilterChange);
    
    return () => {
      window.removeEventListener('filtersChanged', handleFilterChange);
    };
  }, [filters]);

  if (!chartData) return <p>Loading charts...</p>;

  return (
    <div className="chart-wrapper">
      <div className="chart-row">
        <div className="chart-box">
          <h2>Milestone Completion Overview</h2>
          <BarGraph data={chartData.pie} />
        </div>
        <div className="chart-box">
          <h2>Milestone Status Distribution</h2>
          <PieChart data={chartData.pie} />
        </div>
      </div>
    </div>
  );
}

export default AppCoordinator;