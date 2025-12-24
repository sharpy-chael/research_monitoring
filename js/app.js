// import React from "react";
// import { Bar } from "react-chartjs-2";
// import {
//   Chart as ChartJS,
//   CategoryScale,
//   LinearScale,
//   BarElement,
//   Title,
//   Tooltip,
//   Legend
// } from "chart.js";

// // Register components
// ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

// const data = {
//   labels: ["Red", "Blue", "Yellow"],
//   datasets: [
//     {
//       label: "Votes",
//       data: [12, 19, 3],
//       backgroundColor: ["#ff6384", "#36a2eb", "#ffce56"],
//     },
//   ],
// };

// export default function MyChart() {
//   return <Bar data={data} />;
// }

import { LineGraph } from "../react-app/src/line";
function app(){
    return <div className="app">{" "}<LineGraph/>{" "}</div>
}