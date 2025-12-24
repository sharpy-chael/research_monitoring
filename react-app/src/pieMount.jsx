import React from "react";
import { createRoot } from "react-dom/client";
import { PieChart } from "./pie.jsx";

async function fetchPieData() {
  try {
    const res = await fetch("http://localhost/research_monitoring/get_data.php");
    const data = await res.json();
    return data.pie;
  } catch (err) {
    console.error("Failed to fetch pie data:", err);
    return {
      labels: ["Completed", "Pending", "Missing"],
      data: [0, 0, 100]
    };
  }
}

async function mountPie() {
  const pieData = await fetchPieData();
  const container = document.getElementById("pie-root");
  if (!container) return;
  const root = createRoot(container);
  root.render(<PieChart data={pieData} />);
}

mountPie();
