import { useState, useEffect } from "react";
import React from "react";
import { LineGraph } from "./line.jsx";
import { PieChart } from "./pie.jsx";
import "./App.css";

function AppAdvisor() {
  const [chartData, setChartData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch("/research_monitoring/get_advisor_data.php");
        
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        
        if (data.error) {
          setError(data.error);
          return;
        }
        
        setChartData(data);
        console.log("Advisor data fetched:", data);
        
      } catch (err) {
        console.error("Error fetching advisor data:", err);
        setError(err.message);
      }
    };

    fetchData();
    // Refresh every 30 seconds
    const interval = setInterval(fetchData, 30000);
    return () => clearInterval(interval);
  }, []);

  if (error) {
    return (
      <div className="chart-wrapper">
        <p style={{color: 'red', textAlign: 'center'}}>Error loading charts: {error}</p>
      </div>
    );
  }

  if (!chartData) {
    return (
      <div className="chart-wrapper">
        <p style={{textAlign: 'center'}}>Loading charts...</p>
      </div>
    );
  }

  return (
    <div className="chart-wrapper">
      <div className="chart-row">
        <div className="chart-box">
          <h2>Group Progress Comparison</h2>
          <LineGraph data={chartData.line} />
        </div>
        <div className="chart-box">
          <h2>Overall Task Status</h2>
          <PieChart data={chartData.pie} />
        </div>
      </div>
    </div>
  );
}

export default AppAdvisor;