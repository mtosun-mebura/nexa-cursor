import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { HttpClient } from '@angular/common/http';

interface DashboardStats {
  total_users: number;
  total_companies: number;
  total_vacancies: number;
  total_matches: number;
  total_interviews: number;
  active_vacancies: number;
  pending_matches: number;
  completed_interviews: number;
}

interface RecentItem {
  id: number;
  name?: string;
  email?: string;
  title?: string;
  created_at: string;
}

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatProgressSpinnerModule,
    MatSnackBarModule
  ],
  template: `
    <div class="dashboard-container">
      <h1>Admin Dashboard</h1>
      
      <div class="stats-grid" *ngIf="!loading; else loadingTpl">
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon users">people</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.total_users }}</h3>
                <p>Gebruikers</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
        
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon companies">business</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.total_companies }}</h3>
                <p>Bedrijven</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
        
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon vacancies">work</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.total_vacancies }}</h3>
                <p>Vacatures</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
        
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon matches">compare_arrows</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.total_matches }}</h3>
                <p>Matches</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
        
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon active">check_circle</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.active_vacancies }}</h3>
                <p>Actieve Vacatures</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
        
        <mat-card class="stat-card">
          <mat-card-content>
            <div class="stat-content">
              <mat-icon class="stat-icon pending">pending</mat-icon>
              <div class="stat-info">
                <h3>{{ stats.pending_matches }}</h3>
                <p>Wachtende Matches</p>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
      </div>
      
      <ng-template #loadingTpl>
        <div class="loading-container">
          <mat-progress-spinner mode="indeterminate"></mat-progress-spinner>
          <p>Dashboard laden...</p>
        </div>
      </ng-template>
      
      <div class="recent-sections" *ngIf="!loading">
        <div class="recent-section">
          <h2>Recente Gebruikers</h2>
          <mat-card>
            <mat-card-content>
              <table mat-table [dataSource]="recentUsers" class="recent-table">
                <ng-container matColumnDef="name">
                  <th mat-header-cell *matHeaderCellDef>Naam</th>
                  <td mat-cell *matCellDef="let user">{{ user.first_name }} {{ user.last_name }}</td>
                </ng-container>
                
                <ng-container matColumnDef="email">
                  <th mat-header-cell *matHeaderCellDef>E-mail</th>
                  <td mat-cell *matCellDef="let user">{{ user.email }}</td>
                </ng-container>
                
                <ng-container matColumnDef="created">
                  <th mat-header-cell *matHeaderCellDef>Aangemaakt</th>
                  <td mat-cell *matCellDef="let user">{{ user.created_at | date:'short' }}</td>
                </ng-container>
                
                <tr mat-header-row *matHeaderRowDef="userColumns"></tr>
                <tr mat-row *matRowDef="let row; columns: userColumns;"></tr>
              </table>
            </mat-card-content>
          </mat-card>
        </div>
        
        <div class="recent-section">
          <h2>Recente Bedrijven</h2>
          <mat-card>
            <mat-card-content>
              <table mat-table [dataSource]="recentCompanies" class="recent-table">
                <ng-container matColumnDef="name">
                  <th mat-header-cell *matHeaderCellDef>Naam</th>
                  <td mat-cell *matCellDef="let company">{{ company.name }}</td>
                </ng-container>
                
                <ng-container matColumnDef="created">
                  <th mat-header-cell *matHeaderCellDef>Aangemaakt</th>
                  <td mat-cell *matCellDef="let company">{{ company.created_at | date:'short' }}</td>
                </ng-container>
                
                <tr mat-header-row *matHeaderRowDef="companyColumns"></tr>
                <tr mat-row *matRowDef="let row; columns: companyColumns;"></tr>
              </table>
            </mat-card-content>
          </mat-card>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .dashboard-container {
      padding: 20px;
    }
    
    .dashboard-container h1 {
      margin-bottom: 30px;
      color: #333;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .stat-card {
      transition: transform 0.2s;
    }
    
    .stat-card:hover {
      transform: translateY(-2px);
    }
    
    .stat-content {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .stat-icon {
      font-size: 48px;
      width: 48px;
      height: 48px;
    }
    
    .stat-icon.users { color: #2196f3; }
    .stat-icon.companies { color: #4caf50; }
    .stat-icon.vacancies { color: #ff9800; }
    .stat-icon.matches { color: #9c27b0; }
    .stat-icon.active { color: #4caf50; }
    .stat-icon.pending { color: #ff9800; }
    
    .stat-info h3 {
      margin: 0;
      font-size: 32px;
      font-weight: bold;
      color: #333;
    }
    
    .stat-info p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }
    
    .loading-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }
    
    .recent-sections {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .recent-section h2 {
      margin-bottom: 16px;
      color: #333;
    }
    
    .recent-table {
      width: 100%;
    }
    
    .recent-table th {
      font-weight: bold;
      color: #333;
    }
    
    @media (max-width: 768px) {
      .recent-sections {
        grid-template-columns: 1fr;
      }
    }
  `]
})
export class AdminDashboardComponent implements OnInit {
  loading = true;
  stats: DashboardStats = {
    total_users: 0,
    total_companies: 0,
    total_vacancies: 0,
    total_matches: 0,
    total_interviews: 0,
    active_vacancies: 0,
    pending_matches: 0,
    completed_interviews: 0
  };
  
  recentUsers: RecentItem[] = [];
  recentCompanies: RecentItem[] = [];
  
  userColumns = ['name', 'email', 'created'];
  companyColumns = ['name', 'created'];

  constructor(
    private http: HttpClient,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadDashboardData();
  }

  loadDashboardData() {
    this.http.get<any>('http://localhost:8000/api/admin/dashboard').subscribe({
      next: (data) => {
        this.stats = data.stats;
        this.recentUsers = data.recent_users;
        this.recentCompanies = data.recent_companies;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading dashboard:', error);
        this.snackBar.open('Fout bij laden dashboard', 'Sluiten', { duration: 3000 });
        this.loading = false;
      }
    });
  }
}
