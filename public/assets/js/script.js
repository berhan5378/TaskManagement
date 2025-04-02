document.addEventListener('DOMContentLoaded', function() {
  
  // Sidebar Toggle
  const arrow = document.querySelector('.ri-side-bar-line');
  const sideBar = document.querySelector('.side-bar');
  const checkbox = document.querySelector('input[type="checkbox"]');

  arrow.addEventListener('click', () => {
    sideBar.classList.toggle('active_side-bar');
    updateSideBarLinks();
  });

  checkbox.addEventListener('change', () => 
    document.querySelector('body').classList.toggle('themes')
  );

  function updateSideBarLinks() {
    document.querySelectorAll('.side-bar-link').forEach(element => {
      if (!sideBar.classList.contains('active_side-bar')) {
        element.addEventListener('mouseenter', handleMouseEnter);
        element.addEventListener('mouseleave', handleMouseLeave);
      } else {
        element.removeEventListener('mouseenter', handleMouseEnter);
        element.removeEventListener('mouseleave', handleMouseLeave);
      }
    });
  }

  function handleMouseEnter(event) {
    const popup = document.getElementById(this.getAttribute('data-popup'));
    popup.style.cssText = "opacity:1; transition: opacity 1s ease-in-out, background 1s ease-in-out; z-index:100;";
    popup.style.top = event.pageY - 25 + 'px';
    popup.style.left = event.pageX + 30 + 'px';
  }

  function handleMouseLeave() {
    const popup = document.getElementById(this.getAttribute('data-popup'));
    popup.style.cssText = "opacity:0; transition: none; z-index:-1;";
  }

  updateSideBarLinks();

  // Form Handling
  const form = document.querySelector(".taskForm");
  const notifyContainer = document.querySelector(".notify-container");

  if (form) {
    const continueBtn = form.querySelector(".form-btn");
    form.onsubmit = (e) => e.preventDefault();
    continueBtn.onclick = () => updateData(form.getAttribute("action"), null, form);
  }

  // Date Selection
  const date = document.getElementById("DateRange");
  if (date) {
    date.addEventListener("change", function() {
      updateData("/taskManagement/DateRange", this.value);
    });
    updateData("/taskManagement/DateRange", "2025");
  }

  function showNotification(message) {
       const notify = document.createElement('div');
       notify.classList.add('notify', 'active'); 
       notify.innerHTML = message;
       
       notifyContainer.appendChild(notify);
   
       setTimeout(() => {
           notify.classList.remove('active');
           setTimeout(() => notify.remove(), 300); 
       }, 2000);
  }

  function updateData(action, value = null, form = null) {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", action, true);
    
    if (value) xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').getAttribute("content"));

    xhr.onload = () => {
      if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
        if (value === null) {
          showNotification(xhr.response); 
        } else {
          let data = JSON.parse(xhr.responseText);
          let existingBarChart = Chart.getChart("taskCompletionChart");
          if (existingBarChart) existingBarChart.destroy();
          ChartData(data);
        }
      }
    };

    let Data = value ? "dateRange=" + encodeURIComponent(value) : new FormData(form);
    xhr.send(Data);
  }

  function ChartData(data) {
    const barChart = document.getElementById('taskCompletionChart');
    if (barChart) {
      new Chart(barChart, {
        type: 'bar',
        data: {
          labels: data.barChart.labels,
          datasets: [{
            label: 'Tasks Completed',
            data: data.barChart.data,
            backgroundColor: '#4a90e2',
            borderWidth: 0,
            borderRadius: 20,
            barThickness: 15,
            categoryPercentage: 0.3,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { color: '#2e86c1' } },
            y: { beginAtZero: true, ticks: { color: '#2e86c1' } },
          },
        },
      });
    }
  }

  const pieChart = document.getElementById('taskDistributionChart');
  if (pieChart && window.pieChartData?.labels && window.pieChartData?.data) {
    if (window.pieChartData.data.every(value => value === 0)) {
      document.querySelector('.Task-Distribution').style.display = 'none';
      document.querySelector('.Task-Completion').style.display = 'none';
    } else {
      new Chart(pieChart, {
        type: 'pie',
        data: {
          labels: window.pieChartData.labels,
          datasets: [{
            data: window.pieChartData.data,
            backgroundColor: ['#00cc66', '#ffcc00', '#ff6b6b', '#4a90e2'],
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            datalabels: {
              color: '#fff',
              font: { size: 14, weight: 'bold' },
              formatter: (value, context) => {
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return percentage > 0 ? percentage + '%' : null;
              },
            },
            legend: { display: false },
          },
        },
        plugins: [ChartDataLabels],
      });
    }
  }

  const ctx = document.getElementById("taskOverviewLineGraph")?.getContext("2d");
  if (ctx) {
    new Chart(ctx, {
      type: "line",
      data: {
        labels: window.pieChartData.labels,
        datasets: [{
          label: "Task Count",
          data: window.pieChartData.data,
          borderColor: "#4CAF50",
          backgroundColor: "rgba(76, 175, 80, 0.2)",
          borderWidth: 2,
          fill: true,
          tension: 0.4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { ticks: { color: '#2e86c1' } },
          y: { beginAtZero: true, ticks: { color: '#2e86c1' }, title: { display: true, text: "Number of Tasks", color:'#2e86c1' } },
        },
        plugins: { legend: { display: false } },
      },
    });
  }
});
